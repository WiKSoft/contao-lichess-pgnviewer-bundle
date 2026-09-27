<?php

/**
 * Sprachdatei tl_module (Frontend-Modul "lichessPgnviewerReader").
 * Die Viewer-Einstellungen entsprechen denen des Content-Elements, siehe
 * languages/de/tl_content.php.
 */

$GLOBALS['TL_LANG']['tl_module']['lpv_source_legend'] = 'Partie-Einstellungen';
$GLOBALS['TL_LANG']['tl_module']['lpv_dbChess_collections'] = ['Partiesammlung(en) einschränken', 'Nur Partien aus diesen Sammlungen werden über den Alias in der URL gefunden. Leer = alle Sammlungen.'];

$GLOBALS['TL_LANG']['tl_module']['lpv_display_legend'] = 'Anzeige-Einstellungen';
$GLOBALS['TL_LANG']['tl_module']['lpv_showPlayers'] = ['Spielernamen anzeigen', 'Steuert, ob die Spielernamen über/unter dem Brett angezeigt werden.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_showPlayers_option'] = [
    'a' => 'Automatisch (abhängig von vorhandenen PGN-Daten)',
    '1' => 'Immer anzeigen',
    '0' => 'Nie anzeigen',
];

$GLOBALS['TL_LANG']['tl_module']['lpv_showClocks'] = ['Bedenkzeiten anzeigen', 'Zeigt die Uhren neben den Spielernamen an, sofern in der PGN vorhanden.'];

$GLOBALS['TL_LANG']['tl_module']['lpv_showMoves'] = ['Zugliste anzeigen', 'Position und Sichtbarkeit der Zugliste.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_showMoves_option'] = [
    'a' => 'Automatisch (responsiv)',
    'r' => 'Rechts neben dem Brett',
    'l' => 'Links neben dem Brett',
    'b' => 'Unter dem Brett',
    '0' => 'Ausblenden',
];

$GLOBALS['TL_LANG']['tl_module']['lpv_showControls'] = ['Steuerbuttons anzeigen', 'Zeigt die Buttons "Zurück/Menü/Vor" an.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_scrollToMove'] = ['Navigation per Mausrad', 'Erlaubt das Durchblättern der Züge mit dem Mausrad.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_keyboardToMove'] = ['Navigation per Tastatur', 'Erlaubt das Durchblättern der Züge mit den Pfeiltasten.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_showGameInfo'] = ['Partiedaten anzeigen', 'Zeigt Spielernamen, Veranstaltung, Ort, Runde, ECO, Elo, Kommentator und Quelle oberhalb des Bretts an (Angaben, die der lichess-Viewer selbst teilweise nicht darstellt).'];

$GLOBALS['TL_LANG']['tl_module']['lpv_board_legend'] = 'Brett-Einstellungen';
$GLOBALS['TL_LANG']['tl_module']['lpv_orientation'] = ['Brettausrichtung', 'Von welcher Seite aus das Brett dargestellt wird.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_orientation_option'] = [
    '' => 'Automatisch (Orientation-Tag der PGN, sonst Weiß)',
    'white' => 'Weiß unten',
    'black' => 'Schwarz unten',
];

$GLOBALS['TL_LANG']['tl_module']['lpv_initialPlyMode'] = ['Startposition', 'Mit welchem Halbzug der Viewer beim Laden startet.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_initialPlyMode_option'] = [
    's' => 'Grundstellung (1. Zug)',
    'l' => 'Letzter Zug der Partie',
    'n' => 'Bestimmter Halbzug',
];
$GLOBALS['TL_LANG']['tl_module']['lpv_initialPlyNumber'] = ['Halbzug-Nummer', 'Halbzug, mit dem der Viewer startet (0 = Grundstellung).'];

$GLOBALS['TL_LANG']['tl_module']['lpv_drawArrows'] = ['Pfeile/Markierungen erlauben', 'Erlaubt Besuchern, mit der Maus Pfeile auf das Brett zu zeichnen.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_coordinates'] = ['Koordinaten am Rand', 'Zeigt die Feldkoordinaten (a-h, 1-8) am Brettrand an.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_highlightLastMove'] = ['Letzten Zug hervorheben', 'Markiert Start- und Zielfeld des zuletzt gezeigten Zuges.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_highlightCheck'] = ['Schach hervorheben', 'Markiert das Feld des Königs bei Schach.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_animationDuration'] = ['Animationsdauer (ms)', 'Dauer der Zug-Animation in Millisekunden. 0 deaktiviert die Animation.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_blockTouchScroll'] = ['Touch-Scrollen blockieren', 'Verhindert auf Touch-Geräten das Scrollen der Seite beim Bedienen des Bretts.'];

$GLOBALS['TL_LANG']['tl_module']['lpv_design_legend'] = 'Design-Einstellungen';
$GLOBALS['TL_LANG']['tl_module']['lpv_squareLightColorHex'] = ['Farbe helle Felder', 'Hex-Farbcode ohne #, z. B. f0d9b5. Leer = Standardfarbe (f0d9b5).'];
$GLOBALS['TL_LANG']['tl_module']['lpv_squareDarkColorHex'] = ['Farbe dunkle Felder', 'Hex-Farbcode ohne #, z. B. b58863. Leer = Standardfarbe (b58863).'];

$GLOBALS['TL_LANG']['tl_module']['lpv_menu_legend'] = 'Brettmenü-Einstellungen';
$GLOBALS['TL_LANG']['tl_module']['lpv_menuGetPgn'] = ['Menüpunkt "PGN herunterladen"', 'Zeigt im Viewer-Menü einen Download-Link für die aktuelle Partie an.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_menuGetPgnFileName'] = ['Dateiname für PGN-Download', 'Leer lassen für einen automatisch erzeugten Dateinamen.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_menuPractice'] = ['Menüpunkt "Gegen Computer üben"', 'Verlinkt im Viewer-Menü auf lichess.org zum Üben der aktuellen Stellung gegen den Computer.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_menuAnalysisBoard'] = ['Menüpunkt "Analysebrett"', 'Verlinkt im Viewer-Menü auf das lichess.org-Analysebrett.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_lichessLink'] = ['lichess.org-Verknüpfung', 'Erkennt lichess-Partien in der PGN und verlinkt Spieler/Partie auf lichess.org.'];

$GLOBALS['TL_LANG']['tl_module']['lpv_layout_legend'] = 'Layout-Einstellungen';
$GLOBALS['TL_LANG']['tl_module']['lpv_width'] = ['Breite', 'CSS-Breite des Viewers, z. B. 480px oder 100%. Leer = volle Breite.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_boardWidth'] = ['Brettbreite', 'CSS-Breite nur des Schachbretts, z. B. 480px. Wirkt nur, wenn "Zugliste anzeigen" auf "Rechts neben dem Brett" steht - die Zugliste passt sich dann automatisch an, ohne Lücke zum Brett. Bei anderen Zugliste-Positionen bitte stattdessen "Breite" verwenden. Leer = Brett füllt die verfügbare Breite.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_cssClass'] = ['CSS-Klasse(n)', 'Zusätzliche CSS-Klassen für das Wurzelelement des Viewers.'];
$GLOBALS['TL_LANG']['tl_module']['lpv_template'] = ['Eigene Vorlage', 'Abweichende Twig-Vorlage für die Ausgabe (mod_lichessPgnviewerReader_*).'];
