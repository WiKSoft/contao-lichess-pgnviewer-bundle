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
        $this->Template->showGameSelect = \count($games) > 1;
        $this->Template->assetsPath = self::ASSETS_PATH;
        $this->Template->cssClass = trim((string) $this->lpv_cssClass);
        $this->Template->width = trim((string) $this->lpv_width);

        $options = $this->buildViewerOptions();
        $this->Template->options = $options;
        $this->Template->optionsJson = json_encode(
            $options,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
        );
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
                'coordinatesOnSquares' => (bool) $this->lpv_coordinatesOnSquares,
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
