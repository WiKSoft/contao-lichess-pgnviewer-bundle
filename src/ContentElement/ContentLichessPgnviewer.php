<?php

declare(strict_types=1);

namespace Wiksoft\ContaoLichessPgnviewerBundle\ContentElement;

use Contao\ContentElement;
use Contao\Database;
use Contao\File;
use Contao\FilesModel;
use Contao\FrontendTemplate;
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
 * - "d" Interne Datenbank (wiksoft/dbchess-bundle) - nur wählbar, wenn
 *   dieses Bundle installiert ist, siehe DbChessAvailability.
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

    protected $strTemplate = 'ce_lichessPgnviewer';

    protected function compile(): void
    {
        if ($this->lpv_template) {
            $this->Template = new FrontendTemplate($this->lpv_template);
        }

        $pgnText = $this->collectPgnText();
        $games = PgnSplitter::split($pgnText);

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

    private function collectPgnText(): string
    {
        switch ($this->lpv_source) {
            case 't':
                return (string) $this->lpv_text;

            case 'd':
                return $this->collectPgnFromDatabase();

            case 'f':
            default:
                return $this->collectPgnFromFile();
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

    private function collectPgnFromDatabase(): string
    {
        if (!DbChessAvailability::isInstalled()) {
            return '';
        }

        $arrCollection = StringUtil::deserialize($this->dbChess_list_collection, true);

        if (!$arrCollection) {
            return '';
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

        $result = Database::getInstance()
            ->prepare('SELECT * FROM tl_dbChess_games WHERE ' . $where . ' ORDER BY date ASC, id ASC')
            ->execute();

        $pgnText = '';
        while ($result->next()) {
            $pgnText .= $this->buildPgnFromDbRow($result->row()) . "\n\n";
        }

        return trim($pgnText);
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
