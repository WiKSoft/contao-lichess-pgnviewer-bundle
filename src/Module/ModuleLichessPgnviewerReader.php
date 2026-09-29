<?php

declare(strict_types=1);

namespace Wiksoft\ContaoLichessPgnviewerBundle\Module;

use Contao\BackendTemplate;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\Database;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\Module;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Wiksoft\ContaoLichessPgnviewerBundle\Pgn\DbChessAvailability;
use Wiksoft\ContaoLichessPgnviewerBundle\Pgn\PgnSplitter;

/**
 * Frontend-Modul "lichessPgnviewerReader" (registriert in
 * contao/config/config.php unter $GLOBALS['FE_MOD']['schach']
 * ['lichessPgnviewerReader']): zeigt - analog zu Contaos Newsreader - genau
 * EINE Partie aus tl_dbChess_games (wiksoft/contao-dbchess-bundle) mit dem
 * lichess.org PGN-Viewer an. Welche Partie das ist, bestimmt ausschließlich
 * der an die URL angehängte Partie-Alias (auto_item bzw. "items"), z. B.
 * aus einem Link von ContentDbChessList ("dbChess_list_jumpTo").
 *
 * Die Viewer-Einstellungen entsprechen denen des Content-Elements
 * ContentLichessPgnviewer (gleiche Feldnamen, siehe contao/dca/tl_module.php),
 * nur die PGN-Quelle entfällt. Die dafür benötigten Hilfsmethoden sind
 * bewusst aus ContentLichessPgnviewer kopiert statt gemeinsam genutzt, damit
 * das Content-Element unverändert bleibt.
 *
 * Das Standard-Template mod_lichessPgnviewerReader ist eigenständig (nicht
 * von ce_lichessPgnviewer.html.twig abgeleitet), nutzt aber dieselben
 * Variablennamen wie das Content-Element.
 */
class ModuleLichessPgnviewerReader extends Module
{
    private const ASSETS_PATH = '/bundles/wiksoftcontaolichesspgnviewer/lichess-pgn-viewer';

    protected $strTemplate = 'mod_lichessPgnviewerReader';

    public function generate(): string
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### ' . mb_strtoupper($GLOBALS['TL_LANG']['FMD']['lichessPgnviewerReader'][0] ?? 'lichessPgnviewer Reader') . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', ['do' => 'themes', 'table' => 'tl_module', 'act' => 'edit', 'id' => $this->id]));

            return $objTemplate->parse();
        }

        if (!DbChessAvailability::isInstalled()) {
            return '';
        }

        // Wie beim Newsreader: ohne Partie-Alias in der URL keine Ausgabe,
        // damit Liste und Reader auf derselben Seite kombiniert werden können.
        if ('' === $this->getRequestedGameAlias()) {
            return '';
        }

        return parent::generate();
    }

    protected function compile(): void
    {
        if ($this->lpv_template) {
            $this->Template = new FrontendTemplate($this->lpv_template);
            // class, cssID, headline usw. setzt Module::generate() erst nach
            // compile() - hier genügt die Übernahme der Moduldaten.
            $this->Template->setData($this->arrData);
        }

        $row = $this->findGameRow($this->getRequestedGameAlias());

        if (null === $row) {
            throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
        }

        // Link zur übergeordneten Seite (für eigene Templates, die einen
        // "nach oben"-Link zeigen wollen) - wie im Content-Element.
        $this->Template->upHref = null;
        $this->Template->upTitle = null;

        $currentPage = $GLOBALS['objPage'] ?? null;
        $upPage = $currentPage ? PageModel::findByPk($currentPage->pid) : null;

        if ($upPage && 'forward' !== $upPage->type) {
            $this->Template->upHref = $upPage->getFrontendUrl();
            $this->Template->upTitle = StringUtil::specialchars($upPage->title, true);
        }

        // Immer nur die eine Partie - plus ggf. ihre über "sid" verknüpften
        // Varianten (andere Kommentatoren/Quellen derselben Partie), die im
        // Template per Dropdown clientseitig umgeschaltet werden können. Pro
        // Datenbankeintrag wird dabei nur die erste von PgnSplitter
        // gefundene Partie verwendet.
        $games = [];
        foreach ($this->collectSidVariants(Database::getInstance(), $row) as $variant) {
            array_push($games, ...\array_slice($this->buildGamesFromDbRow($variant), 0, 1));
        }

        $this->Template->remark = $games[0]['remark'] ?? '';
        $this->Template->elementId = 'lpv-mod-' . $this->id;
        $this->Template->games = $games;
        $this->Template->hasGames = \count($games) > 0;
        $this->Template->showGameInfo = (bool) $this->lpv_showGameInfo;
        $this->Template->showGameSelect = \count($games) > 1;
        $this->Template->assetsPath = self::ASSETS_PATH;
        $this->Template->assetsVersion = $this->getAssetsVersion();
        $this->Template->cssClass = trim((string) $this->lpv_cssClass);
        $this->Template->width = trim((string) $this->lpv_width);
        $this->Template->boardWidth = trim((string) $this->lpv_boardWidth);
        $this->Template->movesLeft = 'l' === (string) $this->lpv_showMoves;
        $this->Template->movesFlow = 'f' === (string) $this->lpv_movesLayout;
        $this->Template->designStyle = $this->buildDesignStyle();

        $options = $this->buildViewerOptions();
        $this->Template->options = $options;
        $this->Template->optionsJson = json_encode(
            $options,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
        );

        $this->applyPageMeta($row);
    }

    /**
     * Liest den Partie-Alias aus der URL: Contao 5 liefert ihn als
     * auto_item, ältere Links können ihn noch als "items" übergeben
     * (vgl. ContentLichessPgnviewer::getRequestedGameAlias()).
     */
    private function getRequestedGameAlias(): string
    {
        $alias = (string) Input::get('auto_item');

        if ('' === $alias) {
            $alias = (string) Input::get('items');
        }

        return $alias;
    }

    /**
     * Lädt die Partie mit dem gegebenen Alias. Ist das Feld
     * lpv_dbChess_collections befüllt, werden nur Partien aus diesen
     * Sammlungen gefunden (analog zu news_archives beim Newsreader).
     *
     * @return array<string, mixed>|null
     */
    private function findGameRow(string $alias): ?array
    {
        if ('' === $alias) {
            return null;
        }

        $query = 'SELECT * FROM tl_dbChess_games WHERE alias=?';
        $params = [$alias];

        $collections = array_values(array_filter(array_map('intval', StringUtil::deserialize($this->lpv_dbChess_collections, true))));

        if ($collections) {
            $query .= ' AND pid IN (' . implode(',', array_fill(0, \count($collections), '?')) . ')';
            array_push($params, ...$collections);
        }

        $result = Database::getInstance()
            ->prepare($query)
            ->limit(1)
            ->execute(...$params);

        return $result->numRows ? $result->row() : null;
    }

    /**
     * Setzt Seitentitel ("Weiß - Schwarz Ergebnis (Datum)") und
     * Meta-Beschreibung (Veranstaltung, Ort) - analog zu Contaos
     * ModuleNewsReader.
     *
     * @param array<string, mixed> $row
     */
    private function applyPageMeta(array $row): void
    {
        $responseContext = System::getContainer()->get('contao.routing.response_context_accessor')->getResponseContext();

        if (!$responseContext?->has(HtmlHeadBag::class)) {
            return;
        }

        $htmlHeadBag = $responseContext->get(HtmlHeadBag::class);
        $htmlDecoder = System::getContainer()->get('contao.string.html_decoder');

        $clean = static fn (mixed $value): string => \in_array(trim((string) $value), ['', '?', '*'], true)
            ? ''
            : $htmlDecoder->inputEncodedToPlainText(trim((string) $value));

        $white = $clean($row['white'] ?? '');
        $black = $clean($row['black'] ?? '');
        $result = $clean($row['result'] ?? '');
        $date = $this->formatPgnDate((string) ($row['date'] ?? ''));

        $title = $white . ($white && $black ? ' - ' : '') . $black;

        if ('' !== $title) {
            $title .= ($result ? ' ' . $result : '') . ($date ? ' (' . $date . ')' : '');
            $htmlHeadBag->setTitle($title);
        }

        $description = implode(', ', array_filter([$clean($row['event'] ?? ''), $clean($row['site'] ?? '')]));

        if ('' !== $description) {
            $htmlHeadBag->setMetaDescription($description);
        }
    }

    /**
     * PGN-Datum (JJJJ.MM.TT, unbekannte Teile "?") ins Format d.m.Y -
     * dieselbe Logik wie im gameInfo-Block von ce_lichessPgnviewer.html.twig.
     */
    private function formatPgnDate(string $date): string
    {
        $parts = array_map(
            static fn (string $p): string => preg_match('/^[0-9]+$/', $p) ? $p : '',
            array_pad(explode('.', $date), 3, '')
        );

        [$y, $m, $d] = $parts;

        if ($y && $m && $d) {
            return \sprintf('%02d.%02d.%04d', $d, $m, $y);
        }

        if ($y && $m) {
            return \sprintf('%02d.%04d', $m, $y);
        }

        return $y;
    }

    /**
     * Kopie von ContentLichessPgnviewer::collectSidVariants() - siehe dort:
     * liefert die angezeigte Partie (stets an erster Stelle) plus alle über
     * "sid" verknüpften Varianten. Die Sammlungseinschränkung
     * (lpv_dbChess_collections) gilt bewusst nur für die per Alias
     * aufgerufene Partie, nicht für ihre Varianten.
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
     * Kopie von ContentLichessPgnviewer::getAssetsVersion() - siehe dort.
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
     * Kopie von ContentLichessPgnviewer::buildDesignStyle() - siehe dort.
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

    private function sanitizeColorHex(mixed $value): string
    {
        $value = trim((string) $value);

        return preg_match('/^[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : '';
    }

    /**
     * Kopie von ContentLichessPgnviewer::buildGamesFromDbRow() - siehe dort.
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
     * Kopie von ContentLichessPgnviewer::buildPgnFromDbRow() - siehe dort.
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
     * Kopie von ContentLichessPgnviewer::buildViewerOptions() - siehe dort.
     *
     * @return array<string, mixed>
     */
    private function buildViewerOptions(): array
    {
        $initialPly = match ($this->lpv_initialPlyMode) {
            'l' => 'last',
            'n' => (int) $this->lpv_initialPlyNumber,
            default => 0,
        };

        // Start in einer Nebenvariante (nur bei "Bestimmter Halbzug"): die
        // Variante mit Nummer "index" ersetzt den Halbzug "initialPly", der
        // Viewer startet nach "depth" Halbzügen in ihr. Der lichess-pgn-
        // viewer kennt das nicht - lichess-pgn-viewer-init.js entfernt die
        // Option vor der Übergabe und steuert den Zug selbst an.
        $initialVariation = null;
        if ('n' === $this->lpv_initialPlyMode && $initialPly > 0 && (int) $this->lpv_initialVariation > 0) {
            $initialVariation = [
                'index' => (int) $this->lpv_initialVariation,
                'depth' => max(1, (int) $this->lpv_initialVariationDepth),
            ];
        }

        $showPlayers = match ((string) $this->lpv_showPlayers) {
            '1' => true,
            '0' => false,
            default => 'auto',
        };

        $showMoves = match ((string) $this->lpv_showMoves) {
            'r', 'l' => 'right',
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
            'initialVariation' => $initialVariation,
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
