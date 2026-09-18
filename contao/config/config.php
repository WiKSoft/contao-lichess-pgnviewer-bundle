<?php

use Wiksoft\ContaoLichessPgnviewerBundle\ContentElement\ContentLichessPgnviewer;

/**
 * -------------------------------------------------------------------------
 * CONTENT ELEMENTS
 * -------------------------------------------------------------------------
 */
$GLOBALS['TL_CTE']['schach']['lichessPgnviewer'] = ContentLichessPgnviewer::class;

/**
 * -------------------------------------------------------------------------
 * STYLES (nur Layout des Wrappers/der Auswahlliste; die eigentlichen
 * Viewer-Styles kommen aus der vendorten lichess-pgn-viewer.css, siehe
 * contao/templates/ce_lichessPgnviewer.html.twig)
 * -------------------------------------------------------------------------
 */
$GLOBALS['TL_CSS'][] = 'bundles/wiksoftcontaolichesspgnviewer/lpv.css';
