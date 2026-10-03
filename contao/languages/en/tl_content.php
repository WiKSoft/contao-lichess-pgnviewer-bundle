<?php

/**
 * Language file tl_content (content element "lichessPgnviewer").
 */

$GLOBALS['TL_LANG']['tl_content']['lichessPgnviewer'] = ['Chess game (lichess PGN viewer)', 'Allows replaying one or more chess games in the front end with the lichess.org PGN viewer.'];

$GLOBALS['TL_LANG']['tl_content']['lpv_source_legend'] = 'Source settings';
$GLOBALS['TL_LANG']['tl_content']['lpv_source'] = ['PGN source', 'Specify where the PGN data of the game(s) comes from.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_source_option'] = [
    'f' => 'File (.pgn from the file manager)',
    't' => 'Text field (enter PGN directly)',
    'd' => 'Internal database, list with filter/sorting (dbChess)',
    'e' => 'Internal database, individual selection of games (dbChess)',
];

$GLOBALS['TL_LANG']['tl_content']['lpv_file'] = ['PGN file', 'Select a .pgn file from the file manager. It may contain several games.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_text'] = ['PGN text', 'Enter the PGN notation directly. Multiple games are detected automatically.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_text_tooLong'] = 'The PGN text is too long: %s of at most %s bytes. Umlauts count twice, characters like “–” three times. Please shorten it or include the games as a PGN file (source “File”).';

$GLOBALS['TL_LANG']['tl_content']['dbChess_list_collection'] = ['Game collection(s)', 'Select one or more game collections from the dbChess database.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_dbChess_filter'] = ['Custom filter (optional)', 'Additional SQL WHERE condition for tl_dbChess_games, e.g. white=\'Carlsen\'. Only administrators can edit this field.'];

$GLOBALS['TL_LANG']['tl_content']['lpv_dbChess_sortfields'] = ['Sort list by', 'Select one or more fields by which the games found should be sorted. Without a selection, games are sorted by date.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_dbChess_sortfields_option'] = [
    'event' => 'Event',
    'site' => 'Site',
    'date' => 'Date',
    'round' => 'Round',
    'result' => 'Result',
    'white' => 'White',
    'black' => 'Black',
    'eco' => 'ECO',
    'whiteelo' => 'White Elo',
    'blackelo' => 'Black Elo',
    'annotator' => 'Annotator',
    'source' => 'Source',
];

$GLOBALS['TL_LANG']['tl_content']['lpv_dbChess_byorder'] = ['Sort order', 'Select the sort order of the fields selected above.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_dbChess_byorder_option'] = [
    'a' => 'ascending',
    'd' => 'descending',
];

$GLOBALS['TL_LANG']['tl_content']['lpv_dbChess_selection'] = ['Select games', 'Select the games to be displayed individually from the collection(s) selected above.'];

$GLOBALS['TL_LANG']['tl_content']['lpv_display_legend'] = 'Display settings';
$GLOBALS['TL_LANG']['tl_content']['lpv_showPlayers'] = ['Show player names', 'Controls whether the player names are shown above/below the board.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_showPlayers_option'] = [
    'a' => 'Automatic (depending on available PGN data)',
    '1' => 'Always show',
    '0' => 'Never show',
];

$GLOBALS['TL_LANG']['tl_content']['lpv_showClocks'] = ['Show clocks', 'Shows the clocks next to the player names if available in the PGN.'];

$GLOBALS['TL_LANG']['tl_content']['lpv_showMoves'] = ['Show move list', 'Position and visibility of the move list.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_showMoves_option'] = [
    'r' => 'Right of the board',
    'l' => 'Left of the board',
    'b' => 'Below the board',
    '0' => 'Hide',
];
$GLOBALS['TL_LANG']['tl_content']['lpv_movesLayout'] = ['Main line notation', 'Layout of the main line in the move list. Comments and variations are shown as separate blocks in both cases.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_movesLayout_option'] = [
    'c' => 'In columns',
    'f' => 'As running text',
];

$GLOBALS['TL_LANG']['tl_content']['lpv_showControls'] = ['Show control buttons', 'Shows the "Back/Menu/Forward" buttons.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_scrollToMove'] = ['Mouse wheel navigation', 'Allows stepping through the moves with the mouse wheel.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_keyboardToMove'] = ['Keyboard navigation', 'Allows stepping through the moves with the arrow keys.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_showGameInfo'] = ['Show game details', 'Shows player names, event, site, round, ECO, Elo, annotator and source above the board (details that the lichess viewer itself partly does not display).'];

$GLOBALS['TL_LANG']['tl_content']['lpv_board_legend'] = 'Board settings';
$GLOBALS['TL_LANG']['tl_content']['lpv_orientation'] = ['Board orientation', 'From which side the board is displayed.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_orientation_option'] = [
    '' => 'Automatic (Orientation tag of the PGN, otherwise White)',
    'white' => 'White at the bottom',
    'black' => 'Black at the bottom',
];

$GLOBALS['TL_LANG']['tl_content']['lpv_initialPlyMode'] = ['Starting position', 'The half-move at which the viewer starts when loading.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_initialPlyMode_option'] = [
    's' => 'Initial position (1st move)',
    'l' => 'Last move of the game',
    'n' => 'Specific half-move',
];
$GLOBALS['TL_LANG']['tl_content']['lpv_initialPlyNumber'] = ['Half-move number', 'Half-move at which the viewer starts (0 = initial position).'];
$GLOBALS['TL_LANG']['tl_content']['lpv_initialVariation'] = ['Variation', 'Number of the side variation to the half-move above (1 = first variation, 2 = second ...). The variation replaces that half-move. 0 = no variation, start on the main line.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_initialVariationDepth'] = ['Depth in variation', 'Number of half-moves within the variation after which the viewer starts (1 = first move of the variation). If the variation is shorter, its last move is shown.'];

$GLOBALS['TL_LANG']['tl_content']['lpv_drawArrows'] = ['Allow arrows/markers', 'Allows visitors to draw arrows on the board with the mouse.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_coordinates'] = ['Coordinates on the edge', 'Shows the square coordinates (a-h, 1-8) on the edge of the board.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_highlightLastMove'] = ['Highlight last move', 'Marks the origin and destination squares of the last move shown.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_highlightCheck'] = ['Highlight check', 'Marks the king\'s square when in check.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_animationDuration'] = ['Animation duration (ms)', 'Duration of the move animation in milliseconds. 0 disables the animation. Empty = default from the website root, otherwise 250.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_blockTouchScroll'] = ['Block touch scrolling', 'Prevents the page from scrolling on touch devices while using the board.'];

$GLOBALS['TL_LANG']['tl_content']['lpv_design_legend'] = 'Design settings';
$GLOBALS['TL_LANG']['tl_content']['lpv_squareLightColorHex'] = ['Light square colour', 'Hex colour code without #, e.g. f0d9b5. Empty = default from the website root, otherwise f0d9b5.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_squareDarkColorHex'] = ['Dark square colour', 'Hex colour code without #, e.g. b58863. Empty = default from the website root, otherwise b58863.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_pieceSet'] = ['Piece set', 'Look of the pieces on the board. For every set except rhosgfx (CC0), credit the author and licence on the website, e.g. in the imprint. Details in public/pieces/LICENSES.md of the extension.'];

$GLOBALS['TL_LANG']['tl_content']['lpv_menu_legend'] = 'Board menu settings';
$GLOBALS['TL_LANG']['tl_content']['lpv_menuGetPgn'] = ['Menu item "Download PGN"', 'Shows a download link for the current game in the viewer menu.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_menuGetPgnFileName'] = ['File name for PGN download', 'Leave empty for an automatically generated file name.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_menuPractice'] = ['Menu item "Practice with computer"', 'Links to lichess.org in the viewer menu to practise the current position against the computer.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_menuAnalysisBoard'] = ['Menu item "Analysis board"', 'Links to the lichess.org analysis board in the viewer menu.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_lichessLink'] = ['lichess.org link', 'Detects lichess games in the PGN and links players/game to lichess.org.'];

$GLOBALS['TL_LANG']['tl_content']['lpv_layout_legend'] = 'Layout settings';
$GLOBALS['TL_LANG']['tl_content']['lpv_width'] = ['Width', 'CSS width of the viewer, e.g. 480px or 100%. Empty = default from the website root, otherwise full width.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_boardWidth'] = ['Board width', 'Maximum width of the chessboard, e.g. 480px. Only takes effect if "Show move list" is set to "Right" or "Left of the board". If space runs short, the board gets narrower and the move list keeps at least 232px; the board also adapts to the window height. For other move list positions, please use "Width" instead. Empty = default from the website root, otherwise as large as space and window height allow.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_cssClass'] = ['CSS class(es)', 'Additional CSS classes for the root element of the viewer.'];
$GLOBALS['TL_LANG']['tl_content']['lpv_template'] = ['Content element template', 'Here you can select the content element template.'];
