<?php

use Contao\Backend;
use Wiksoft\ContaoLichessPgnviewerBundle\Pgn\DbChessAvailability;

/**
 * DCA-Erweiterung tl_module für das Frontend-Modul "lichessPgnviewerReader".
 *
 * Die Viewer-Einstellungen sind 1:1 aus contao/dca/tl_content.php
 * (Content-Element "lichessPgnviewer") übernommen - gleiche Feldnamen,
 * Defaults und SQL-Definitionen. Die PGN-Quelle entfällt: die Partie
 * bestimmt der Alias in der URL (siehe ModuleLichessPgnviewerReader).
 */

/**
 * Palette
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'lpv_initialPlyMode';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'lpv_menuGetPgn';

$GLOBALS['TL_DCA']['tl_module']['palettes']['lichessPgnviewerReader'] = '
	{title_legend},name,headline,type;
	' . (DbChessAvailability::isInstalled() ? '{lpv_source_legend},lpv_dbChess_collections;' : '') . '
	{lpv_display_legend},lpv_showPlayers,lpv_showClocks,lpv_showMoves,lpv_movesLayout,lpv_showControls,lpv_scrollToMove,lpv_keyboardToMove,lpv_showGameInfo;
	{lpv_board_legend},lpv_orientation,lpv_initialPlyMode,lpv_drawArrows,lpv_coordinates,lpv_highlightLastMove,lpv_highlightCheck,lpv_animationDuration,lpv_blockTouchScroll;
	{lpv_design_legend},lpv_squareLightColorHex,lpv_squareDarkColorHex;
	{lpv_menu_legend},lpv_menuGetPgn,lpv_menuPractice,lpv_menuAnalysisBoard,lpv_lichessLink;
	{lpv_layout_legend},lpv_width,lpv_boardWidth,lpv_cssClass;
	{template_legend:hide},lpv_template;
	{protected_legend:hide},protected;
	{expert_legend:hide},guest,cssID';

$GLOBALS['TL_DCA']['tl_module']['subpalettes']['lpv_initialPlyMode_n'] = 'lpv_initialPlyNumber,lpv_initialVariation,lpv_initialVariationDepth';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['lpv_menuGetPgn'] = 'lpv_menuGetPgnFileName';

/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_dbChess_collections'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_dbChess_collections'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'foreignKey' => 'tl_dbChess_collection.name',
    'eval' => ['multiple' => true, 'tl_class' => 'clr'],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_showPlayers'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_showPlayers'],
    'default' => 'a',
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['a', '1', '0'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['lpv_showPlayers_option'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(1) NOT NULL default 'a'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_showClocks'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_showClocks'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_showMoves'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_showMoves'],
    'default' => 'a',
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['a', 'r', 'l', 'b', '0'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['lpv_showMoves_option'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(1) NOT NULL default 'a'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_movesLayout'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_movesLayout'],
    'default' => 'c',
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['c', 'f'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['lpv_movesLayout_option'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(1) NOT NULL default 'c'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_showControls'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_showControls'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_scrollToMove'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_scrollToMove'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_keyboardToMove'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_keyboardToMove'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_showGameInfo'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_showGameInfo'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_orientation'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_orientation'],
    'default' => '',
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['', 'white', 'black'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['lpv_orientation_option'],
    'eval' => ['includeBlankOption' => false, 'tl_class' => 'w50'],
    'sql' => "varchar(5) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_initialPlyMode'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_initialPlyMode'],
    'default' => 's',
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['s', 'l', 'n'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['lpv_initialPlyMode_option'],
    'eval' => ['submitOnChange' => true, 'tl_class' => 'w50'],
    'sql' => "varchar(1) NOT NULL default 's'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_initialPlyNumber'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_initialPlyNumber'],
    'default' => '0',
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'digit', 'tl_class' => 'w50'],
    'sql' => "smallint(5) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_initialVariation'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_initialVariation'],
    'default' => '0',
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'digit', 'tl_class' => 'w50 clr'],
    'sql' => "smallint(5) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_initialVariationDepth'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_initialVariationDepth'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'natural', 'tl_class' => 'w50'],
    'sql' => "smallint(5) unsigned NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_drawArrows'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_drawArrows'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12 clr'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_coordinates'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_coordinates'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_highlightLastMove'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_highlightLastMove'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_highlightCheck'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_highlightCheck'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_animationDuration'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_animationDuration'],
    'default' => '250',
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'digit', 'tl_class' => 'w50'],
    'sql' => "smallint(5) unsigned NOT NULL default '250'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_blockTouchScroll'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_blockTouchScroll'],
    'default' => '',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_squareLightColorHex'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_squareLightColorHex'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50', 'colorpicker' => true, 'placeholder' => 'f0d9b5'],
    'sql' => "varchar(6) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_squareDarkColorHex'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_squareDarkColorHex'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50', 'colorpicker' => true, 'placeholder' => 'b58863'],
    'sql' => "varchar(6) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_menuGetPgn'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_menuGetPgn'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['submitOnChange' => true, 'tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_menuGetPgnFileName'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_menuGetPgnFileName'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_menuPractice'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_menuPractice'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_menuAnalysisBoard'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_menuAnalysisBoard'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_lichessLink'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_lichessLink'],
    'default' => '1',
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_width'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_width'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50 clr', 'placeholder' => '100%'],
    'sql' => "varchar(32) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_boardWidth'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_boardWidth'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50', 'placeholder' => '480px'],
    'sql' => "varchar(32) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_cssClass'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_cssClass'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['lpv_template'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['lpv_template'],
    'default' => 'mod_lichessPgnviewerReader',
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => ['tl_module_lichessPgnviewer', 'getTemplates'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];

class tl_module_lichessPgnviewer extends Backend
{
    public function getTemplates(): array
    {
        return $this->getTemplateGroup('mod_lichessPgnviewerReader');
    }
}
