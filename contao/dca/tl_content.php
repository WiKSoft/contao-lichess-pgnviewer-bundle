<?php

use Contao\Backend;
use Contao\BackendUser;
use Contao\Database;
use Contao\DataContainer;
use Contao\StringUtil;
use Wiksoft\ContaoLichessPgnviewerBundle\Pgn\DbChessAvailability;

/**
 * DCA-Erweiterung tl_content für das Content-Element "lichessPgnviewer".
 */

/**
 * Config
 */
$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = ['tl_content_lichessPgnviewer', 'protectFilterField'];

/**
 * Palette
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'lpv_source';
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'lpv_initialPlyMode';
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'lpv_menuGetPgn';

$GLOBALS['TL_DCA']['tl_content']['palettes']['lichessPgnviewer'] = '
	{type_legend},type,headline;
	{lpv_source_legend},lpv_source;
	{lpv_display_legend},lpv_showPlayers,lpv_showClocks,lpv_showMoves,lpv_movesLayout,lpv_showControls,lpv_scrollToMove,lpv_keyboardToMove,lpv_showGameInfo;
	{lpv_board_legend},lpv_orientation,lpv_initialPlyMode,lpv_drawArrows,lpv_coordinates,lpv_highlightLastMove,lpv_highlightCheck,lpv_animationDuration,lpv_blockTouchScroll;
	{lpv_design_legend},lpv_squareLightColorHex,lpv_squareDarkColorHex;
	{lpv_menu_legend},lpv_menuGetPgn,lpv_menuPractice,lpv_menuAnalysisBoard,lpv_lichessLink;
	{lpv_layout_legend},lpv_width,lpv_boardWidth,lpv_cssClass;
	{template_legend:hide},lpv_template;
	{protected_legend:hide},protected;
	{expert_legend:hide},guest,cssID,space;
	{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['subpalettes']['lpv_source_f'] = 'lpv_file';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['lpv_source_t'] = 'lpv_text';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['lpv_source_d'] = 'dbChess_list_collection,lpv_dbChess_filter,lpv_dbChess_sortfields,lpv_dbChess_byorder';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['lpv_source_e'] = 'dbChess_list_collection,lpv_dbChess_selection';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['lpv_initialPlyMode_n'] = 'lpv_initialPlyNumber,lpv_initialVariation,lpv_initialVariationDepth';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['lpv_menuGetPgn'] = 'lpv_menuGetPgnFileName';

/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_source'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_source'],
    'default' => 'f',
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => ['tl_content_lichessPgnviewer', 'getSourceOptions'],
    'reference' => &$GLOBALS['TL_LANG']['tl_content']['lpv_source_option'],
    'eval' => ['submitOnChange' => true, 'tl_class' => 'w50'],
    'sql' => "varchar(1) NOT NULL default 'f'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_file'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_file'],
    'exclude' => true,
    'inputType' => 'fileTree',
    'eval' => [
        'mandatory' => true,
        'files' => true,
        'filesOnly' => true,
        'extensions' => 'pgn',
        'fieldType' => 'radio',
        'tl_class' => 'clr',
    ],
    'sql' => 'binary(16) NULL',
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_text'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_text'],
    'exclude' => true,
    'inputType' => 'textarea',
    'eval' => ['mandatory' => true, 'decodeEntities' => true, 'class' => 'monospace', 'rows' => 12, 'tl_class' => 'clr'],
    'sql' => 'text NULL',
];

$GLOBALS['TL_DCA']['tl_content']['fields']['dbChess_list_collection'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['dbChess_list_collection'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'foreignKey' => 'tl_dbChess_collection.name',
    'eval' => [
        'mandatory' => true,
        'includeBlankOption' => false,
        // Nötig, damit die Partienliste von lpv_dbChess_selection (Quelle
        // "Einzelauswahl") nach einem Sammlungswechsel sofort neu geladen
        // wird, siehe getGameSelectionOptions() - analog zu pgn4webs
        // dbChess_list_collection (dort seit jeher submitOnChange).
        'submitOnChange' => true,
        'multiple' => true,
        'tl_class' => 'clr',
    ],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_dbChess_filter'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_dbChess_filter'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['decodeEntities' => true, 'tl_class' => 'clr'],
    // Freier SQL-Ausdruck: nur Administratoren dürfen ihn ändern
    'save_callback' => [['tl_content_lichessPgnviewer', 'saveFilterField']],
    'sql' => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_dbChess_sortfields'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_dbChess_sortfields'],
    'exclude' => true,
    'inputType' => 'checkboxWizard',
    'options' => ['event', 'site', 'date', 'round', 'result', 'white', 'black', 'eco', 'whiteelo', 'blackelo', 'annotator', 'source'],
    'reference' => &$GLOBALS['TL_LANG']['tl_content']['lpv_dbChess_sortfields_option'],
    'eval' => ['tl_class' => 'clr', 'multiple' => true],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_dbChess_byorder'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_dbChess_byorder'],
    'default' => 'a',
    'exclude' => true,
    'inputType' => 'radio',
    'options' => ['a', 'd'],
    'reference' => &$GLOBALS['TL_LANG']['tl_content']['lpv_dbChess_byorder_option'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(1) NOT NULL default 'a'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_dbChess_selection'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_dbChess_selection'],
    'exclude' => true,
    'inputType' => 'checkboxWizard',
    'options_callback' => ['tl_content_lichessPgnviewer', 'getGameSelectionOptions'],
    // "lpv-selection-scroll" gibt dem Backend-CSS (contao/config/config.php,
    // public/backend.css) einen eindeutigen Anker, um NUR dieses Feld auf
    // eine feste Höhe mit Scrollbalken zu begrenzen - bei Partiesammlungen
    // mit sehr vielen Partien (z. B. "Nimzowitsch") wird die Liste sonst
    // extrem lang und die restlichen Formularfelder rutschen weit nach unten.
    'eval' => ['mandatory' => true, 'multiple' => true, 'tl_class' => 'clr lpv-selection-scroll'],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_showPlayers'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_showPlayers'],
    'default' => 'a',
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['a', '1', '0'],
    'reference' => &$GLOBALS['TL_LANG']['tl_content']['lpv_showPlayers_option'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(1) NOT NULL default 'a'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_showClocks'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_showClocks'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_showMoves'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_showMoves'],
    'default' => 'a',
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['a', 'r', 'l', 'b', '0'],
    'reference' => &$GLOBALS['TL_LANG']['tl_content']['lpv_showMoves_option'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(1) NOT NULL default 'a'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_movesLayout'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_movesLayout'],
    'default' => 'c',
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['c', 'f'],
    'reference' => &$GLOBALS['TL_LANG']['tl_content']['lpv_movesLayout_option'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(1) NOT NULL default 'c'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_showControls'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_showControls'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_scrollToMove'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_scrollToMove'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_keyboardToMove'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_keyboardToMove'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_showGameInfo'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_showGameInfo'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_orientation'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_orientation'],
    'default' => '',
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['', 'white', 'black'],
    'reference' => &$GLOBALS['TL_LANG']['tl_content']['lpv_orientation_option'],
    'eval' => ['includeBlankOption' => false, 'tl_class' => 'w50'],
    'sql' => "varchar(5) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_initialPlyMode'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_initialPlyMode'],
    'default' => 's',
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['s', 'l', 'n'],
    'reference' => &$GLOBALS['TL_LANG']['tl_content']['lpv_initialPlyMode_option'],
    'eval' => ['submitOnChange' => true, 'tl_class' => 'w50'],
    'sql' => "varchar(1) NOT NULL default 's'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_initialPlyNumber'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_initialPlyNumber'],
    'default' => '0',
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'digit', 'tl_class' => 'w50'],
    'sql' => "smallint(5) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_initialVariation'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_initialVariation'],
    'default' => '0',
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'digit', 'tl_class' => 'w50 clr'],
    'sql' => "smallint(5) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_initialVariationDepth'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_initialVariationDepth'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'natural', 'tl_class' => 'w50'],
    'sql' => "smallint(5) unsigned NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_drawArrows'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_drawArrows'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12 clr'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_coordinates'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_coordinates'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_highlightLastMove'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_highlightLastMove'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_highlightCheck'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_highlightCheck'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_animationDuration'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_animationDuration'],
    'default' => '250',
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'digit', 'tl_class' => 'w50'],
    'sql' => "smallint(5) unsigned NOT NULL default '250'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_blockTouchScroll'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_blockTouchScroll'],
    'default' => '',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_squareLightColorHex'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_squareLightColorHex'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50', 'colorpicker' => true, 'placeholder' => 'f0d9b5'],
    'sql' => "varchar(6) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_squareDarkColorHex'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_squareDarkColorHex'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50', 'colorpicker' => true, 'placeholder' => 'b58863'],
    'sql' => "varchar(6) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_menuGetPgn'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_menuGetPgn'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['submitOnChange' => true, 'tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_menuGetPgnFileName'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_menuGetPgnFileName'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_menuPractice'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_menuPractice'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_menuAnalysisBoard'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_menuAnalysisBoard'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_lichessLink'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_lichessLink'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_width'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_width'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50 clr', 'placeholder' => '100%'],
    'sql' => "varchar(32) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_boardWidth'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_boardWidth'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50', 'placeholder' => '480px'],
    'sql' => "varchar(32) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_cssClass'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_cssClass'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['lpv_template'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['lpv_template'],
    'default' => 'ce_lichessPgnviewer',
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => ['tl_content_lichessPgnviewer', 'getTemplates'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];

class tl_content_lichessPgnviewer extends Backend
{
    /**
     * Der Filter ist ein freier SQL-Ausdruck, der unverändert in die Abfrage
     * auf tl_dbChess_games übernommen wird. Für Nicht-Administratoren wird
     * das Feld daher schreibgeschützt angezeigt.
     */
    public function protectFilterField(DataContainer $dc): void
    {
        if (!BackendUser::getInstance()->isAdmin) {
            $GLOBALS['TL_DCA']['tl_content']['fields']['lpv_dbChess_filter']['eval']['readonly'] = true;
        }
    }

    /**
     * Verhindert, dass Nicht-Administratoren den Filter ändern (auch nicht
     * über einen manipulierten Request): es bleibt der gespeicherte Wert.
     */
    public function saveFilterField(mixed $value, DataContainer $dc): mixed
    {
        if (BackendUser::getInstance()->isAdmin) {
            return $value;
        }

        $record = $dc->getCurrentRecord();

        return (string) ($record['lpv_dbChess_filter'] ?? '');
    }

    /**
     * Quellen "Interne Datenbank" und "Einzelauswahl" nur anbieten, wenn
     * wiksoft/contao-dbchess-bundle installiert ist (siehe DbChessAvailability).
     */
    public function getSourceOptions(): array
    {
        $options = ['f', 't'];

        if (DbChessAvailability::isInstalled()) {
            $options[] = 'd';
            $options[] = 'e';
        }

        return $options;
    }

    public function getTemplates(): array
    {
        return $this->getTemplateGroup('ce_lichessPgnviewer');
    }

    /**
     * Options-Callback für lpv_dbChess_selection (Quelle "Einzelauswahl"):
     * listet die einzelnen Partien der im Feld "dbChess_list_collection"
     * bereits gewählten Sammlung(en) auf, analog zu pgn4webs
     * tl_content_pgn4web::getGameList().
     */
    public function getGameSelectionOptions(DataContainer $dc): array
    {
        $collection = [];
        $arrCollection = StringUtil::deserialize($dc->activeRecord->dbChess_list_collection ?? null, true);
        foreach ($arrCollection as $valueCollection) {
            $collection[] = 'pid=' . (int) $valueCollection;
        }
        if (!$collection) {
            $collection[] = 'pid=0';
        }
        $pidCollection = implode(' OR ', $collection);

        $result = Database::getInstance()
            ->prepare('SELECT * FROM tl_dbChess_games WHERE ' . $pidCollection . ' ORDER BY date ASC, id ASC')
            ->execute();

        $options = [];
        while ($result->next()) {
            $row = $result->row();
            $label = trim(
                ($row['date'] ? $row['date'] . ' ' : '')
                . ($row['white'] ? $row['white'] . ' ' : '')
                . ($row['result'] ? $row['result'] . ' ' : '')
                . ($row['black'] ? $row['black'] . ' ' : '')
                . ($row['source'] ? '(' . $row['source'] . ')' : '')
            );
            $options[$row['id']] = $label ?: '#' . $row['id'];
        }

        return $options;
    }
}
