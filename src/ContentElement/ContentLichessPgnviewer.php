<?php

declare(strict_types=1);

namespace Wiksoft\ContaoLichessPgnviewerBundle\ContentElement;

use Contao\Config;
use Contao\ContentElement;
use Contao\Database;
use Contao\File;
use Contao\FilesModel;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Wiksoft\ContaoLichessPgnviewerBundle\Pgn\DbChessAvailability;
use Wiksoft\ContaoLichessPgnviewerBundle\Pgn\PgnSplitter;

/**
 * Content-Element "contao-lichess-pgnviewer": stellt eine oder mehrere
 * Schachpartien (PGN) im Frontend nachspielbar dar, gerendert mit dem
 * lichess.org PGN-Viewer (https://github.com/lichess-org/pgn-viewer). Der
 * Viewer selbst läuft vollständig im Browser (ES-Modul, siehe
 * contao/public/lichess-pgn-viewer.min.js); dieses Element liefert ihm nur
 * die PGN-Daten sowie die Konfigurationsoptionen.
 *
 * Als PGN-Quelle (lpv_source) stehen zur Verfügung:
 * - "f" Datei (.pgn-Datei aus der Dateiverwaltung)
 * - "t" Textfeld (PGN direkt im Content-Element eingegeben)
 * - "d" Interne Datenbank, Liste aus Sammlung(en) mit optionalem Filter/
 *   Sortierung sowie optionaler Rundennavigation (wiksoft/dbchess-bundle) -
 *   nur wählbar, wenn dieses Bundle installiert ist, siehe DbChessAvailability.
 * - "e" Interne Datenbank, manuelle Einzelauswahl einzelner Partien aus
 *   einer Sammlung (analog zur pgn4web-Quelle "b"/fromBase) - ebenfalls
 *   nur wählbar, wenn wiksoft/dbchess-bundle installiert ist.
 *
 * Enthält die Quelle mehrere Partien, werden sie serverseitig aufgeteilt
 * (siehe PgnSplitter) und komplett an das Template übergeben; die Auswahl
 * per Dropdown sowie ihre Beschriftung/Sortierung erfolgt bewusst im
 * Twig-Template (contao/templates/ce_lichessPgnviewer.html.twig), damit sie
 * dort ohne PHP-Änderung angepasst werden kann. Der Wechsel der Partie
 * selbst passiert rein clientseitig per JavaScript (keine Seiten-Neuladung).
 */
class ContentLichessPgnviewer extends ContentElement
{
    private const ASSETS_PATH = '/bundles/wiksoftcontaolichesspgnviewer/lichess-pgn-viewer';

    /**
     * Whitelist für lpv_dbChess_sortfields (identisch mit den 'options' des
     * Feldes in tl_content.php) - wird zusätzlich zur DCA-Beschränkung im
     * Backend hier erneut geprüft, bevor ein Feldname in die ORDER-BY-
     * Klausel interpoliert wird (Defense in Depth gegen SQL-Injection über
     * einen manipulierten Datensatz).
     */
    private const SORTABLE_FIELDS = ['event', 'site', 'date', 'round', 'result', 'white', 'black', 'eco', 'whiteelo', 'blackelo', 'annotator', 'source'];

    protected $strTemplate = 'ce_lichessPgnviewer';

    protected function compile(): void
    {
        if ($this->lpv_template) {
            $this->Template = new FrontendTemplate($this->lpv_template);
        }

        // Vorbelegung für Twig (strict_variables): wird nur bei Quelle "d"
        // mit aktivierter Rundennavigation (lpv_dbChess_roundNav) in
        // applyRoundNavigation() überschrieben.
        $this->Template->roundNavEnabled = false;
        $this->Template->currentRound = '';
        $this->Template->prevRound = null;
        $this->Template->nextRound = null;
        $this->Template->prevRoundHref = null;
        $this->Template->nextRoundHref = null;

        // Vorbelegung für Twig (strict_variables): wird nur bei Quelle "d" in
        // applyGameNavigation() überschrieben. Anders als roundNav (Navigation
        // über die Runden-Spalte) verlinkt dies stets zur vorherigen/nächsten
        // Partie in der sortierten Ergebnisliste - unabhängig davon, ob
        // lpv_dbChess_roundNav aktiviert ist. Gedacht für Quellen ohne
        // sinnvolle Rundenangabe (z. B. Simultanpartien), siehe
        // ce_lichessPgnviewer_simultan.
        $this->Template->prevGameHref = null;
        $this->Template->prevGameWhite = '';
        $this->Template->prevGameBlack = '';
        $this->Template->nextGameHref = null;
        $this->Template->nextGameWhite = '';
        $this->Template->nextGameBlack = '';

        // Link zur übergeordneten Seite, für benutzerdefinierte Templates
        // (z. B. ce_lichessPgnviewer_turnier), die neben der Rundennavigation
        // zusätzlich einen "nach oben"-Link zeigen wollen - analog zu
        // ContentPgn4web::generateBoardTemplate(). Quellenunabhängig gesetzt,
        // da es sich um reine Seitenhierarchie handelt, nicht um dbChess-Daten.
        $this->Template->upHref = null;
        $this->Template->upTitle = null;

        $currentPage = $GLOBALS['objPage'] ?? null;
        $upPage = $currentPage ? PageModel::findByPk($currentPage->pid) : null;

        if ($upPage && 'forward' !== $upPage->type) {
            $this->Template->upHref = $upPage->getFrontendUrl();
            $this->Template->upTitle = StringUtil::specialchars($upPage->title, true);
        }

        $games = $this->collectGames();

        // Nur bei den dbChess-Quellen ("d"/"e") befüllt, siehe buildGamesFromDbRow().
        // Bezieht sich bewusst nur auf die erste/initial geladene Partie (games[0]) -
        // bei mehreren Partien wechselt der Remark-Block beim Umschalten im
        // gameSelect-Dropdown NICHT mit (anders als z. B. die Felder in
        // "gameInfo"), da das dafür nötige clientseitige Nachbilden von Contaos
        // HTML-Sanitizing/Insert-Tag-Ersetzung ein unnötiges Sicherheitsrisiko wäre.
        $this->Template->remark = $games[0]['remark'] ?? '';

        $this->Template->elementId = 'lpv-ce-' . $this->id;
        $this->Template->games = $games;
        $this->Template->hasGames = \count($games) > 0;
        $this->Template->showGameInfo = (bool) $this->lpv_showGameInfo;
        $this->Template->showGameSelect = \count($games) > 1;
        $this->Template->assetsPath = self::ASSETS_PATH;
        $this->Template->assetsVersion = $this->getAssetsVersion();
        $this->Template->cssClass = trim((string) $this->lpv_cssClass);
        $this->Template->width = trim((string) $this->lpv_width);
        $this->Template->boardWidth = trim((string) $this->lpv_boardWidth);
        $this->Template->designStyle = $this->buildDesignStyle();

        $options = $this->buildViewerOptions();
        $this->Template->options = $options;
        $this->Template->optionsJson = json_encode(
            $options,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
        );
    }

    /**
     * lichess-pgn-viewer-init.js und lichess-pgn-viewer.css werden im
     * Twig-Template direkt als <script src>/<link href> eingebunden statt
     * über $GLOBALS['TL_JAVASCRIPT']/TL_CSS (Contaos Combiner würde das
     * ES-Modul lichess-pgn-viewer-init.js sonst potenziell mit anderen
     * Skripten kombinieren und dabei "type=module" verlieren). Der
     * öffentliche Ordner public/bundles/.../ wird von Apache/Nginx aber mit
     * einem sehr langen Cache-Control: max-age (typischerweise 1 Jahr)
     * ausgeliefert - ohne eine sich ändernde URL würden Browser nach einer
     * Aktualisierung dieser Dateien (z. B. neue Funktionen im Init-Script)
     * weiterhin die alte, gecachte Version verwenden. Ein an die Dateien
     * gekoppelter "?v="-Parameter erzwingt bei jeder Änderung automatisch
     * eine neue URL und damit ein erneutes Laden.
     */
    private function getAssetsVersion(): string
    {
        $projectDir = System::getContainer()->getParameter('kernel.project_dir');
        $assetsDir = $projectDir . '/public' . self::ASSETS_PATH;

        $mtimes = [];
        foreach (['lichess-pgn-viewer-init.js', 'lichess-pgn-viewer.css'] as $file) {
            $path = $assetsDir . '/' . $file;
            if (is_file($path)) {
                $mtimes[] = filemtime($path);
            }
        }

        return $mtimes ? (string) max($mtimes) : '1';
    }

    /**
     * Baut aus den optionalen lpv_square{Light,Dark}ColorHex-Feldern (siehe
     * tl_content.php) einen CSS-Custom-Property-Deklarationsblock, der im
     * Template auf dem INNEREN #{{ elementId }}-Div gesetzt wird (siehe
     * getAssetsVersion()-Kommentar zur Trennung von TL_CSS/TL_JAVASCRIPT vs.
     * direkt im Template eingebundenen Assets - dasselbe Div trägt bereits
     * das bedingte "max-width"-Style für lpv_width). Das äußere .lpv-wrapper-
     * Div mit Contaos eigenem generischem cssID-Style bleibt unberührt.
     *
     * Nur tatsächlich befüllte Felder werden ausgegeben; ein leeres Feld
     * lässt den bisherigen Default unverändert (Rückfall auf
     * public/lpv.css). Hintergrund-, Akzent- und Textfarbe gibt es hier
     * bewusst nicht mehr als eigene Content-Element-Felder - sie werden
     * zentral für alle Viewer-Instanzen über die projektweite
     * files/css/pgnviewer.scss gesteuert.
     */
    private function buildDesignStyle(): string
    {
        $vars = [];

        if ($color = $this->sanitizeColorHex($this->lpv_squareLightColorHex)) {
            $vars['--lpv-square-light'] = $color;
        }

        if ($color = $this->sanitizeColorHex($this->lpv_squareDarkColorHex)) {
            $vars['--lpv-square-dark'] = $color;
        }

        if (!$vars) {
            return '';
        }

        $declarations = '';
        foreach ($vars as $property => $color) {
            $declarations .= $property . ':#' . $color . ';';
        }

        return $declarations;
    }

    /**
     * Contaos colorpicker-Widget speichert nur die reinen Hex-Ziffern ohne
     * führendes '#' (Contao-Kernkonvention, siehe jedes *ColorHex-Feld in
     * wiksoft/pgn4web-bundle). Trotzdem wird strikt gegen ein 6-stelliges
     * Hex-Muster geprüft, damit ein manipulierter oder fehlerhafter
     * Datensatz nicht unvalidiert in die generierte style="..."-Deklaration
     * gelangen kann.
     */
    private function sanitizeColorHex(mixed $value): string
    {
        $value = trim((string) $value);

        return preg_match('/^[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : '';
    }

    /**
     * @return list<array{pgn: string, headers: array<string, string>, remark?: string}>
     */
    private function collectGames(): array
    {
        switch ($this->lpv_source) {
            case 'd':
                return $this->collectGamesFromDatabase();

            case 'e':
                return $this->collectGamesFromSelection();

            case 't':
                return PgnSplitter::split((string) $this->lpv_text);

            case 'f':
            default:
                return PgnSplitter::split($this->collectPgnFromFile());
        }
    }

    private function collectPgnFromFile(): string
    {
        if (!$this->lpv_file) {
            return '';
        }

        $fileModel = FilesModel::findByUuid($this->lpv_file);

        if (null === $fileModel) {
            return '';
        }

        $projectDir = System::getContainer()->getParameter('kernel.project_dir');

        if (!is_file($projectDir . '/' . $fileModel->path)) {
            return '';
        }

        $file = new File($fileModel->path);

        return $file->getContent();
    }

    /**
     * Entfernt aus der für Alias-Suche, Rundennavigation und Vor-/Zurück-
     * Navigation verwendeten Liste alle über "sid" (wiksoft/dbchess-bundle)
     * verknüpften Partien bis auf die zuerst gelistete - analog zu
     * ContentPgn4web::compile() ("Verknüpfte Partien entfernen, bis auf die
     * zuerst gelistete"). Ohne diese Deduplizierung würde z. B. eine als
     * "hervorgehoben" markierte Partie zusammen mit ihrer verknüpften
     * Variante zweimal in der Navigation auftauchen. Die entfernten Partien
     * bleiben über collectSidVariants() weiterhin als Dropdown-Varianten der
     * verbleibenden Partie erreichbar.
     *
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array<string, mixed>>
     */
    private function dedupeBySid(array $rows): array
    {
        $remaining = $rows;

        foreach ($rows as $key => $row) {
            if (!isset($remaining[$key])) {
                continue;
            }

            $sid = StringUtil::deserialize($row['sid'] ?? null, true);
            if (!$sid) {
                continue;
            }

            foreach ($sid as $sidId) {
                if ((int) $sidId === (int) $row['id']) {
                    continue;
                }

                foreach ($remaining as $otherKey => $otherRow) {
                    if ((int) $otherRow['id'] === (int) $sidId) {
                        unset($remaining[$otherKey]);
                    }
                }
            }
        }

        return array_values($remaining);
    }

    /**
     * @return list<array{pgn: string, headers: array<string, string>, remark: string, alias: string}>
     */
    private function collectGamesFromDatabase(): array
    {
        if (!DbChessAvailability::isInstalled()) {
            return [];
        }

        $arrCollection = StringUtil::deserialize($this->dbChess_list_collection, true);

        if (!$arrCollection) {
            return [];
        }

        $collection = [];
        foreach ($arrCollection as $pid) {
            $collection[] = 'pid=' . (int) $pid;
        }
        $where = '(' . implode(' OR ', $collection) . ')';

        $filter = trim((string) $this->lpv_dbChess_filter);
        if ($filter) {
            $where .= ' AND (' . StringUtil::decodeEntities($filter) . ')';
        }

        $database = Database::getInstance();
        $byOrder = 'd' === $this->lpv_dbChess_byorder ? 'DESC' : 'ASC';
        $orderBy = $this->buildOrderBy($byOrder);

        $result = $database
            ->prepare('SELECT * FROM tl_dbChess_games WHERE ' . $where . ' ORDER BY ' . $orderBy)
            ->execute();

        $rows = [];
        while ($result->next()) {
            $rows[] = $result->row();
        }

        if (!$rows) {
            return [];
        }

        $rows = $this->dedupeBySid($rows);

        $aliasRow = $this->findRowByAlias($rows, $this->getRequestedGameAlias());

        $currentRow = $this->lpv_dbChess_roundNav
            ? $this->applyRoundNavigation($database, $where, $byOrder, $rows, $aliasRow)
            : ($aliasRow ?? $rows[0]);

        $this->applyGameNavigation($rows, $currentRow);

        $games = [];
        foreach ($this->collectSidVariants($database, $currentRow) as $row) {
            array_push($games, ...$this->buildGamesFromDbRow($row));
        }

        return $games;
    }

    /**
     * Schreibt Links zur vorherigen/nächsten Partie in der sortierten
     * Ergebnisliste ins Template (siehe Vorbelegung in compile()) - anders
     * als applyRoundNavigation() unabhängig von der Spalte "round" und
     * unabhängig davon, ob lpv_dbChess_roundNav aktiviert ist. Gedacht für
     * Templates wie ce_lichessPgnviewer_simultan, deren Partien keine
     * sinnvolle Rundenangabe haben und stattdessen von Partie zu Partie
     * blättern.
     *
     * @param list<array<string, mixed>> $rows
     * @param array<string, mixed>       $currentRow
     */
    private function applyGameNavigation(array $rows, array $currentRow): void
    {
        $index = null;
        $currentId = (int) $currentRow['id'];

        foreach ($rows as $key => $row) {
            if ((int) $row['id'] === $currentId) {
                $index = $key;
                break;
            }
        }

        if (null === $index) {
            return;
        }

        $prevRow = $index > 0 ? $rows[$index - 1] : null;
        $nextRow = isset($rows[$index + 1]) ? $rows[$index + 1] : null;

        $this->Template->prevGameHref = $this->buildGameUrl($prevRow);
        $this->Template->prevGameWhite = (string) ($prevRow['white'] ?? '');
        $this->Template->prevGameBlack = (string) ($prevRow['black'] ?? '');
        $this->Template->nextGameHref = $this->buildGameUrl($nextRow);
        $this->Template->nextGameWhite = (string) ($nextRow['white'] ?? '');
        $this->Template->nextGameBlack = (string) ($nextRow['black'] ?? '');
    }

    /**
     * Liest den von Contao über auto_item bzw. den Query-Parameter "items"
     * bereitgestellten URL-Fragment-Wert - analog zu
     * ContentPgn4web::compile() und ModulePgn4webReader::generate() im
     * pgn4web-Bundle. Ein Link aus ContentDbChessList (Feld
     * "dbChess_list_jumpTo", siehe dbChess_list_default.html.twig) zeigt auf
     * genau diesen Parameter, befüllt mit dem Alias der angeklickten Partie.
     */
    private function getRequestedGameAlias(): string
    {
        if (!isset($_GET['items']) && Config::get('useAutoItem') && isset($_GET['auto_item'])) {
            Input::setGet('items', Input::get('auto_item'));
        }

        return (string) Input::get('items');
    }

    /**
     * Sucht in $rows die Partie mit passendem Alias (siehe
     * getRequestedGameAlias()). Ohne Alias oder ohne Treffer wird null
     * zurückgegeben - die Aufrufer fallen dann auf ihren jeweiligen Default
     * zurück (erste Partie bzw. erste Partie der Default-Runde).
     *
     * @param list<array<string, mixed>> $rows
     *
     * @return array<string, mixed>|null
     */
    private function findRowByAlias(array $rows, string $alias): ?array
    {
        if ('' === $alias) {
            return null;
        }

        foreach ($rows as $row) {
            if (($row['alias'] ?? '') === $alias) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Baut die ORDER-BY-Klausel aus lpv_dbChess_sortfields + lpv_dbChess_byorder.
     * Ohne Auswahl bleibt das bisherige Verhalten (Datum, dann ID) erhalten.
     * "round" wird - wie bei pgn4web - numerisch sortiert, damit z. B. Runde
     * "2" vor Runde "10" einsortiert wird statt alphabetisch danach.
     *
     * Die als "hervorgehoben" markierte Partie (Feld gameFeatured aus
     * wiksoft/dbchess-bundle) wird als nachgestelltes Kriterium (Tie-Breaker)
     * angehängt - analog zu ContentPgn4web::compile(). Sie hat damit nur bei
     * ansonsten gleichen Sortierwerten Vorrang und überstimmt nicht die
     * eigentliche Sortierung (z. B. nach Datum).
     */
    private function buildOrderBy(string $byOrder): string
    {
        $arrSorting = array_intersect(
            StringUtil::deserialize($this->lpv_dbChess_sortfields, true),
            self::SORTABLE_FIELDS
        );

        $fields = [];
        foreach ($arrSorting as $field) {
            $fields[] = ('round' === $field ? 'CAST(round AS UNSIGNED)' : $field) . ' ' . $byOrder;
        }

        $orderBy = $fields ? implode(', ', $fields) : 'date ASC, id ASC';

        return $orderBy . ', gameFeatured DESC';
    }

    /**
     * Ermittelt bei aktivierter Rundennavigation (lpv_dbChess_roundNav) die
     * anzuzeigende Runde und schreibt Vor-/Zurück-Informationen ins
     * Template. "$where" enthält an dieser Stelle ausschließlich admin-
     * seitig konfigurierte Bedingungen (Sammlung(en) + optionaler eigener
     * Filter).
     *
     * Die anzuzeigende Runde wird ausschließlich aus der per "items"
     * angeforderten Partie abgeleitet (siehe $aliasRow), nicht mehr über
     * einen eigenen "round"-URL-Parameter - der Partie-Alias identifiziert
     * die Partie bereits eindeutig. Ohne Treffer (kein Alias in der URL
     * bzw. kein Alias, der zu einer Partie mit Rundenangabe passt) wird die
     * erste verfügbare Runde angezeigt. Die Vor-/Zurück-Links verlinken auf
     * die Partie-Alias-URL der jeweiligen Nachbarrunde (siehe
     * buildGameUrl()), damit ein Wechsel der Runde stets automatisch auch
     * das "items"-Fragment für die dortige Partie mitführt.
     *
     * @param list<array<string, mixed>> $rows
     * @param array<string, mixed>|null  $aliasRow
     *
     * @return array<string, mixed>
     */
    private function applyRoundNavigation(Database $database, string $where, string $byOrder, array $rows, ?array $aliasRow): array
    {
        $result = $database
            ->prepare("SELECT DISTINCT round FROM tl_dbChess_games WHERE {$where} AND round != '' ORDER BY CAST(round AS UNSIGNED) {$byOrder}")
            ->execute();

        $rounds = $result->fetchEach('round');

        if (!$rounds) {
            return $aliasRow ?? $rows[0];
        }

        $currentRound = (null !== $aliasRow && \in_array((string) $aliasRow['round'], $rounds, true))
            ? (string) $aliasRow['round']
            : $rounds[0];

        $index = array_search($currentRound, $rounds, true);
        $prevRound = $index > 0 ? $rounds[$index - 1] : null;
        $nextRound = isset($rounds[$index + 1]) ? $rounds[$index + 1] : null;

        $this->Template->roundNavEnabled = true;
        $this->Template->currentRound = $currentRound;
        $this->Template->prevRound = $prevRound;
        $this->Template->nextRound = $nextRound;
        $this->Template->prevRoundHref = $this->buildGameUrl($this->findRowForRound($rows, $prevRound));
        $this->Template->nextRoundHref = $this->buildGameUrl($this->findRowForRound($rows, $nextRound));

        if (null !== $aliasRow && (string) $aliasRow['round'] === $currentRound) {
            return $aliasRow;
        }

        foreach ($rows as $row) {
            if ((string) $row['round'] === $currentRound) {
                return $row;
            }
        }

        return $aliasRow ?? $rows[0];
    }

    /**
     * Sucht in $rows die erste Partie einer bestimmten Runde - genutzt von
     * applyRoundNavigation(), um für die Vor-/Zurück-Links der Nachbarrunde
     * eine konkrete Partie (und damit deren Alias) zu ermitteln.
     *
     * @param list<array<string, mixed>> $rows
     *
     * @return array<string, mixed>|null
     */
    private function findRowForRound(array $rows, ?string $round): ?array
    {
        if (null === $round) {
            return null;
        }

        foreach ($rows as $row) {
            if ((string) $row['round'] === $round) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Baut die Frontend-URL der aktuellen Seite mit dem Alias der
     * übergebenen Partie als "items"-Fragment - analog zu
     * ContentPgn4web::generatePrevNextLinks() bzw.
     * ContentDbChessList::compile() (siehe dort "getFrontendUrl('/' .
     * $alias)"), inklusive derselben Berücksichtigung von
     * Config::get('useAutoItem')/'disableAlias' für den "/items/"-Fallback.
     */
    private function buildGameUrl(?array $row): ?string
    {
        $alias = trim((string) ($row['alias'] ?? ''));

        if ('' === $alias) {
            return null;
        }

        $currentPage = $GLOBALS['objPage'] ?? null;

        if (!$currentPage) {
            return null;
        }

        $prefix = (Config::get('useAutoItem') && !Config::get('disableAlias')) ? '/' : '/items/';

        return $currentPage->getFrontendUrl($prefix . $alias);
    }

    /**
     * Ermittelt zur aktuell anzuzeigenden Partie alle über das Feld "sid"
     * (wiksoft/dbchess-bundle) verknüpften Varianten - typischerweise
     * dieselbe Partie, erfasst von unterschiedlichen Kommentatoren/Quellen.
     * Diese Varianten wurden zuvor per dedupeBySid() aus der Navigationsliste
     * entfernt (siehe dort, analog zu ContentPgn4web::compile()) und werden
     * hier stattdessen im Auswahl-Dropdown angeboten. Ohne "sid"-Verknüpfung
     * wird nur die Partie selbst zurückgegeben. Die aktuelle Partie steht
     * dabei stets an erster Stelle, die übrigen Varianten in der von der
     * Datenbank gelieferten Reihenfolge.
     *
     * @param array<string, mixed> $currentRow
     *
     * @return list<array<string, mixed>>
     */
    private function collectSidVariants(Database $database, array $currentRow): array
    {
        $sidIds = array_values(array_unique(array_filter(
            array_map('intval', StringUtil::deserialize($currentRow['sid'] ?? null, true))
        )));

        if (!\in_array((int) $currentRow['id'], $sidIds, true)) {
            return [$currentRow];
        }

        $placeholders = implode(',', array_fill(0, \count($sidIds), '?'));
        $result = $database
            ->prepare("SELECT * FROM tl_dbChess_games WHERE id IN ({$placeholders})")
            ->execute(...$sidIds);

        $currentId = (int) $currentRow['id'];
        $variants = [$currentRow];

        while ($result->next()) {
            $row = $result->row();

            if ((int) $row['id'] !== $currentId) {
                $variants[] = $row;
            }
        }

        return $variants;
    }

    /**
     * Quelle "e" (Einzelauswahl): baut die Partien ausschließlich aus den
     * über lpv_dbChess_selection manuell gewählten Partien - ohne Filter
     * oder Sortier-UI, analog zu pgn4webs Quelle "b"/fromBase.
     *
     * @return list<array{pgn: string, headers: array<string, string>, remark: string, alias: string}>
     */
    private function collectGamesFromSelection(): array
    {
        if (!DbChessAvailability::isInstalled()) {
            return [];
        }

        $arrSelection = array_map('intval', StringUtil::deserialize($this->lpv_dbChess_selection, true));

        if (!$arrSelection) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, \count($arrSelection), '?'));

        $result = Database::getInstance()
            ->prepare("SELECT * FROM tl_dbChess_games WHERE id IN ({$placeholders})")
            ->execute(...$arrSelection);

        $games = [];
        while ($result->next()) {
            array_push($games, ...$this->buildGamesFromDbRow($result->row()));
        }

        return $games;
    }

    /**
     * Baut aus einer Zeile der Tabelle tl_dbChess_games die (normalerweise
     * genau eine) daraus resultierende(n) Partie(n) inkl. "remark" - getrennt
     * von buildPgnFromDbRow()/PgnSplitter aufgerufen (statt wie bisher alle
     * Zeilen zu einem PGN-Text zu verketten und diesen als Ganzes zu
     * splitten), damit "remark" zuverlässig der richtigen Partie zugeordnet
     * werden kann, auch wenn eine Partie z. B. mangels "Event"-Tag von
     * PgnSplitter nicht zuverlässig von der nächsten hätte abgegrenzt werden
     * können.
     *
     * @param array<string, mixed> $row
     *
     * @return list<array{pgn: string, headers: array<string, string>, remark: string, alias: string}>
     */
    private function buildGamesFromDbRow(array $row): array
    {
        $games = PgnSplitter::split($this->buildPgnFromDbRow($row));
        $remark = trim((string) ($row['remark'] ?? ''));
        $alias = (string) ($row['alias'] ?? '');

        foreach ($games as &$game) {
            $game['remark'] = $remark;
            $game['alias'] = $alias;
        }
        unset($game);

        return $games;
    }

    /**
     * Baut aus einer Zeile der Tabelle tl_dbChess_games (wiksoft/dbchess-bundle)
     * einen vollständigen Partie-PGN-Text (Tag-Pairs + Zugfolge).
     *
     * @param array<string, mixed> $row
     */
    private function buildPgnFromDbRow(array $row): string
    {
        $pgnText = '';

        $tags = [
            'event' => 'Event', 'site' => 'Site', 'date' => 'Date', 'round' => 'Round',
            'white' => 'White', 'black' => 'Black', 'result' => 'Result', 'eco' => 'ECO',
            'whiteelo' => 'WhiteElo', 'blackelo' => 'BlackElo', 'source' => 'Source',
            'annotator' => 'Annotator',
        ];

        foreach ($tags as $column => $tag) {
            if (!empty($row[$column])) {
                $pgnText .= '[' . $tag . ' "' . $row[$column] . '"]' . "\n";
            }
        }

        if (!empty($row['fen'])) {
            $pgnText .= '[FEN "' . $row['fen'] . '"]' . "\n";
            $pgnText .= '[SetUp "1"]' . "\n";
        }

        $pgnText .= "\n" . html_entity_decode((string) ($row['pgn'] ?? ''), ENT_QUOTES);

        return trim($pgnText);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildViewerOptions(): array
    {
        $initialPly = match ($this->lpv_initialPlyMode) {
            'l' => 'last',
            'n' => (int) $this->lpv_initialPlyNumber,
            default => 0,
        };

        $showPlayers = match ((string) $this->lpv_showPlayers) {
            '1' => true,
            '0' => false,
            default => 'auto',
        };

        $showMoves = match ((string) $this->lpv_showMoves) {
            'r' => 'right',
            'b' => 'bottom',
            '0' => false,
            default => 'auto',
        };

        $options = [
            'showPlayers' => $showPlayers,
            'showClocks' => (bool) $this->lpv_showClocks,
            'showMoves' => $showMoves,
            'showControls' => (bool) $this->lpv_showControls,
            'scrollToMove' => (bool) $this->lpv_scrollToMove,
            'keyboardToMove' => (bool) $this->lpv_keyboardToMove,
            'initialPly' => $initialPly,
            'drawArrows' => (bool) $this->lpv_drawArrows,
            'menu' => [
                'getPgn' => [
                    'enabled' => (bool) $this->lpv_menuGetPgn,
                ],
                'practiceWithComputer' => [
                    'enabled' => (bool) $this->lpv_menuPractice,
                ],
                'analysisBoard' => [
                    'enabled' => (bool) $this->lpv_menuAnalysisBoard,
                ],
            ],
            'lichess' => $this->lpv_lichessLink ? 'https://lichess.org' : false,
            'chessground' => [
                'coordinates' => (bool) $this->lpv_coordinates,
                'blockTouchScroll' => (bool) $this->lpv_blockTouchScroll,
                'highlight' => [
                    'lastMove' => (bool) $this->lpv_highlightLastMove,
                    'check' => (bool) $this->lpv_highlightCheck,
                ],
                'animation' => [
                    'duration' => (int) ($this->lpv_animationDuration ?: 0),
                ],
            ],
        ];

        if ($this->lpv_orientation) {
            $options['orientation'] = $this->lpv_orientation;
        }

        if ($cssClass = trim((string) $this->lpv_cssClass)) {
            $options['classes'] = $cssClass;
        }

        if ((bool) $this->lpv_menuGetPgn && $this->lpv_menuGetPgnFileName) {
            $options['menu']['getPgn']['fileName'] = $this->lpv_menuGetPgnFileName;
        }

        return $options;
    }
}
