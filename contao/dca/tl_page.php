<?php

use Contao\Controller;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\DataContainer;
use Wiksoft\ContaoLichessPgnviewerBundle\Settings\ViewerSettings;

/**
 * Vorgaben für den lichess PGN-Viewer am Startpunkt der Website (Typ "root"
 * und "rootfallback"), siehe ViewerSettings.
 *
 * Die Felder sind Kopien der (bereits vererbbar gemachten) Felder aus
 * tl_content, mit derselben Speicherung: '' = eingebauter Standard der
 * Erweiterung. Dazu kommt lpv_locked: die Einstellungen, die für alle
 * Elemente und Module fest gelten sollen, auch wenn diese einen eigenen Wert
 * haben.
 */
Controller::loadDataContainer('tl_content');

$fields = ViewerSettings::getFields('tl_page');

foreach ($fields as $field) {
    $config = $GLOBALS['TL_DCA']['tl_content']['fields'][$field];

    // Am Startpunkt bedeutet "leer" den eingebauten Standard
    if ('lpv_pieceSet' === $field) {
        $config['options_callback'] = static fn (DataContainer|null $dc = null): array => ViewerSettings::getPieceSetOptions($dc);
    } elseif (!empty($config['eval']['includeBlankOption'])) {
        $config['eval']['blankOptionLabel'] = sprintf($GLOBALS['TL_LANG']['MSC']['lpv_inheritBuiltin'] ?? 'Extension default (%s)', ViewerSettings::formatValue('tl_content', $field, null));
    } else {
        $config['eval']['placeholder'] = ViewerSettings::formatValue('tl_content', $field, null);
    }

    $GLOBALS['TL_DCA']['tl_page']['fields'][$field] = $config;
}

// Eigene Erklärung am Startpunkt: wofür die Vorlage gilt
$GLOBALS['TL_DCA']['tl_page']['fields']['lpv_template']['label'] = &$GLOBALS['TL_LANG']['tl_page']['lpv_template'];

$GLOBALS['TL_DCA']['tl_page']['fields']['lpv_locked'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_page']['lpv_locked'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'options' => $fields,
    'reference' => array_map(
        static fn (string $field): string => (string) ($GLOBALS['TL_LANG']['tl_content'][$field][0] ?? $field),
        array_combine($fields, $fields),
    ),
    'eval' => ['multiple' => true, 'tl_class' => 'clr lpv-locked-field'],
    'sql' => 'blob NULL',
];

$palette = PaletteManipulator::create()
    ->addLegend('lpv_legend', 'publish_legend', PaletteManipulator::POSITION_BEFORE, true)
    ->addField([...$fields, 'lpv_locked'], 'lpv_legend', PaletteManipulator::POSITION_APPEND)
;

foreach (['root', 'rootfallback'] as $type) {
    if (isset($GLOBALS['TL_DCA']['tl_page']['palettes'][$type])) {
        $palette->applyToPalette($type, 'tl_page');
    }
}
