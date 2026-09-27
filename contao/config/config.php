<?php

use Wiksoft\ContaoLichessPgnviewerBundle\ContentElement\ContentLichessPgnviewer;
use Wiksoft\ContaoLichessPgnviewerBundle\EventListener\LoadDataContainerListener;
use Wiksoft\ContaoLichessPgnviewerBundle\Module\ModuleLichessPgnviewerReader;

/**
 * -------------------------------------------------------------------------
 * CONTENT ELEMENTS
 * -------------------------------------------------------------------------
 */
$GLOBALS['TL_CTE']['schach']['lichessPgnviewer'] = ContentLichessPgnviewer::class;

/**
 * -------------------------------------------------------------------------
 * FRONT END MODULES
 * -------------------------------------------------------------------------
 */
$GLOBALS['FE_MOD']['schach']['lichessPgnviewerReader'] = ModuleLichessPgnviewerReader::class;

/**
 * -------------------------------------------------------------------------
 * STYLES (nur Layout des Wrappers/der Auswahlliste; die eigentlichen
 * Viewer-Styles kommen aus der vendorten lichess-pgn-viewer.css, siehe
 * contao/templates/ce_lichessPgnviewer.html.twig)
 * -------------------------------------------------------------------------
 */
$GLOBALS['TL_CSS'][] = 'bundles/wiksoftcontaolichesspgnviewer/lpv.css';

/**
 * -------------------------------------------------------------------------
 * BACKEND-STYLES: backend.css wird NICHT hier direkt eingebunden (die
 * TL_MODE-Konstante, mit der man das früher aufs Backend beschränkt hätte,
 * existiert in dieser Contao-Version nicht mehr), sondern nur beim Laden
 * der tl_content-/tl_module-DCA, siehe LoadDataContainerListener.
 * -------------------------------------------------------------------------
 */
$GLOBALS['TL_HOOKS']['loadDataContainer'][] = [LoadDataContainerListener::class, 'onLoadDataContainer'];
