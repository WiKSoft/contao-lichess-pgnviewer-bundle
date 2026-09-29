<?php

/**
 * Language file tl_module (front end module "lichessPgnviewerReader").
 * The viewer settings correspond to those of the content element, see
 * languages/en/tl_content.php.
 */

$GLOBALS['TL_LANG']['tl_module']['lpv_source_legend'] = 'Game settings';
$GLOBALS['TL_LANG']['tl_module']['lpv_dbChess_collections'] = ['Restrict game collection(s)', 'Only games from these collections are found via the alias in the URL. Empty = all collections.'];

$GLOBALS['TL_LANG']['tl_module']['lpv_display_legend'] = 'Display settings';
$GLOBALS['TL_LANG']['tl_module']['lpv_showPlayers'] = ['Show player names', 'Controls whether the player names are shown above/below the board.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_showPlayers_option'] = [
    'a' => 'Automatic (depending on available PGN data)',
    '1' => 'Always show',
    '0' => 'Never show',
];

$GLOBALS['TL_LANG']['tl_module']['lpv_showClocks'] = ['Show clocks', 'Shows the clocks next to the player names if available in the PGN.'];

$GLOBALS['TL_LANG']['tl_module']['lpv_showMoves'] = ['Show move list', 'Position and visibility of the move list.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_showMoves_option'] = [
    'a' => 'Automatic (responsive)',
    'r' => 'Right of the board',
    'l' => 'Left of the board',
    'b' => 'Below the board',
    '0' => 'Hide',
];
$GLOBALS['TL_LANG']['tl_module']['lpv_movesLayout'] = ['Main line notation', 'Layout of the main line in the move list. Comments and variations are shown as separate blocks in both cases.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_movesLayout_option'] = [
    'c' => 'In columns',
    'f' => 'As running text',
];

$GLOBALS['TL_LANG']['tl_module']['lpv_showControls'] = ['Show control buttons', 'Shows the "Back/Menu/Forward" buttons.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_scrollToMove'] = ['Mouse wheel navigation', 'Allows stepping through the moves with the mouse wheel.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_keyboardToMove'] = ['Keyboard navigation', 'Allows stepping through the moves with the arrow keys.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_showGameInfo'] = ['Show game details', 'Shows player names, event, site, round, ECO, Elo, annotator and source above the board (details that the lichess viewer itself partly does not display).'];

$GLOBALS['TL_LANG']['tl_module']['lpv_board_legend'] = 'Board settings';
$GLOBALS['TL_LANG']['tl_module']['lpv_orientation'] = ['Board orientation', 'From which side the board is displayed.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_orientation_option'] = [
    '' => 'Automatic (Orientation tag of the PGN, otherwise White)',
    'white' => 'White at the bottom',
    'black' => 'Black at the bottom',
];

$GLOBALS['TL_LANG']['tl_module']['lpv_initialPlyMode'] = ['Starting position', 'The half-move at which the viewer starts when loading.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_initialPlyMode_option'] = [
    's' => 'Initial position (1st move)',
    'l' => 'Last move of the game',
    'n' => 'Specific half-move',
];
$GLOBALS['TL_LANG']['tl_module']['lpv_initialPlyNumber'] = ['Half-move number', 'Half-move at which the viewer starts (0 = initial position).'];
$GLOBALS['TL_LANG']['tl_module']['lpv_initialVariation'] = ['Variation', 'Number of the side variation to the half-move above (1 = first variation, 2 = second ...). The variation replaces that half-move. 0 = no variation, start on the main line.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_initialVariationDepth'] = ['Depth in variation', 'Number of half-moves within the variation after which the viewer starts (1 = first move of the variation). If the variation is shorter, its last move is shown.'];

$GLOBALS['TL_LANG']['tl_module']['lpv_drawArrows'] = ['Allow arrows/markers', 'Allows visitors to draw arrows on the board with the mouse.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_coordinates'] = ['Coordinates on the edge', 'Shows the square coordinates (a-h, 1-8) on the edge of the board.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_highlightLastMove'] = ['Highlight last move', 'Marks the origin and destination squares of the last move shown.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_highlightCheck'] = ['Highlight check', 'Marks the king\'s square when in check.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_animationDuration'] = ['Animation duration (ms)', 'Duration of the move animation in milliseconds. 0 disables the animation.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_blockTouchScroll'] = ['Block touch scrolling', 'Prevents the page from scrolling on touch devices while using the board.'];

$GLOBALS['TL_LANG']['tl_module']['lpv_design_legend'] = 'Design settings';
$GLOBALS['TL_LANG']['tl_module']['lpv_squareLightColorHex'] = ['Light square colour', 'Hex colour code without #, e.g. f0d9b5. Empty = default colour (f0d9b5).'];
$GLOBALS['TL_LANG']['tl_module']['lpv_squareDarkColorHex'] = ['Dark square colour', 'Hex colour code without #, e.g. b58863. Empty = default colour (b58863).'];

$GLOBALS['TL_LANG']['tl_module']['lpv_menu_legend'] = 'Board menu settings';
$GLOBALS['TL_LANG']['tl_module']['lpv_menuGetPgn'] = ['Menu item "Download PGN"', 'Shows a download link for the current game in the viewer menu.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_menuGetPgnFileName'] = ['File name for PGN download', 'Leave empty for an automatically generated file name.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_menuPractice'] = ['Menu item "Practice with computer"', 'Links to lichess.org in the viewer menu to practise the current position against the computer.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_menuAnalysisBoard'] = ['Menu item "Analysis board"', 'Links to the lichess.org analysis board in the viewer menu.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_lichessLink'] = ['lichess.org link', 'Detects lichess games in the PGN and links players/game to lichess.org.'];

$GLOBALS['TL_LANG']['tl_module']['lpv_layout_legend'] = 'Layout settings';
$GLOBALS['TL_LANG']['tl_module']['lpv_width'] = ['Width', 'CSS width of the viewer, e.g. 480px or 100%. Empty = full width.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_boardWidth'] = ['Board width', 'CSS width of the chessboard only, e.g. 480px. Only takes effect if "Show move list" is set to "Right of the board" - the move list then adjusts automatically without a gap to the board. For other move list positions, please use "Width" instead. Empty = board fills the available width.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_cssClass'] = ['CSS class(es)', 'Additional CSS classes for the root element of the viewer.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_template'] = ['Custom template', 'Alternative Twig template for the output (mod_lichessPgnviewerReader_*).'];
