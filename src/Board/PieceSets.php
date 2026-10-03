<?php

declare(strict_types=1);

namespace Wiksoft\ContaoLichessPgnviewerBundle\Board;

use Contao\StringUtil;

/**
 * Die mitgelieferten Figurensätze (public/pieces, Urheber und Lizenzen siehe
 * public/pieces/LICENSES.md).
 *
 * "cburnett" ist der Standard des lichess-pgn-viewer und steckt bereits in
 * dessen CSS. Für jeden anderen Satz liegt eine public/pieces/<satz>.css, die
 * die Hintergrundbilder der Figuren für .lpv-pieces--<satz> überschreibt. Sie
 * wird nur geladen, wenn der Satz gewählt ist.
 */
final class PieceSets
{
    public const DEFAULT = 'cburnett';

    public const PUBLIC_PATH = '/bundles/wiksoftcontaolichesspgnviewer/pieces';

    /**
     * Satz => [Urheber, Lizenz]
     */
    public const SETS = [
        'cburnett' => ['Colin M. L. Burnett', 'GPL v2+'],
        'merida' => ['Armando Hernandez Marroquin', 'GPL v2+'],
        'mpchess' => ['Maxime Chupin', 'GPL v3+'],
        'chessnut' => ['Alexis Luengas', 'Apache 2.0'],
        'fantasy' => ['Maurizio Monge', 'MIT'],
        'celtic' => ['Maurizio Monge', 'MIT'],
        'rhosgfx' => ['RhosGFX', 'CC0 1.0'],
        'kiwen-suwi' => ['neverRare', 'CC BY 4.0'],
        'totoy' => ['Kosal Sen', 'CC BY 4.0'],
    ];

    /**
     * Gültiger Satz oder der Standard (z. B. bei einem leeren oder
     * veralteten Wert in der Datenbank).
     */
    public static function normalize(mixed $set): string
    {
        $set = (string) $set;

        return isset(self::SETS[$set]) ? $set : self::DEFAULT;
    }

    /**
     * URL der CSS-Datei des Satzes (mit Änderungszeit als Cache-Parameter)
     * oder null für den Standard, der keine eigene Datei braucht.
     */
    public static function getStylesheet(string $set, string $projectDir): string|null
    {
        $set = self::normalize($set);

        if (self::DEFAULT === $set) {
            return null;
        }

        $path = self::PUBLIC_PATH.'/'.$set.'.css';
        $mtime = @filemtime($projectDir.'/public'.$path);

        return $path.'?v='.($mtime ?: '1');
    }

    /**
     * Optionen für das Radio-Feld im Backend: Name, Urheber und Lizenz mit
     * einer kleinen Vorschau. Die Vorschau sind Hintergrundbilder aus
     * public/backend.css (.lpv-pieceset__preview--<satz>), der Browser lädt
     * sie nur, wenn das Feld angezeigt wird.
     *
     * $light/$dark: Feldfarben für die Vorschau (--lpv-sq-light/-dark, 6
     * Hex-Ziffern), public/backend.js aktualisiert sie beim Ändern der
     * Farbfelder. $blankLabel: Beschriftung der Option "Standard" (Wert ''),
     * deren Vorschau $blankSet zeigt; null = keine solche Option.
     * $mergeBlank: Die Option "Standard" steht für $blankSet selbst (z. B. am
     * Startpunkt, wo "Standard" = cburnett ist). Sie zeigt dann dessen
     * Urheber und Lizenz, und $blankSet erscheint nicht noch einmal.
     *
     * @return array<string, string>
     */
    public static function getBackendOptions(string|null $light = null, string|null $dark = null, string|null $blankLabel = null, string $blankSet = self::DEFAULT, bool $mergeBlank = false): array
    {
        $style = '';

        foreach (['light' => $light, 'dark' => $dark] as $name => $color) {
            if (preg_match('/^[0-9a-f]{6}$/i', (string) $color)) {
                $style .= '--lpv-sq-'.$name.':#'.$color.';';
            }
        }

        $styleAttr = $style ? ' style="'.$style.'"' : '';
        $options = [];

        $blankSet = self::normalize($blankSet);

        if (null !== $blankLabel) {
            [$author, $license] = self::SETS[$blankSet];
            $options[''] = self::renderOption($blankSet, $styleAttr, StringUtil::specialchars($blankLabel), $mergeBlank ? StringUtil::specialchars($author).' · '.StringUtil::specialchars($license) : '');
        }

        foreach (self::SETS as $set => [$author, $license]) {
            if ($mergeBlank && null !== $blankLabel && $set === $blankSet) {
                continue;
            }

            $options[$set] = self::renderOption($set, $styleAttr, $set, StringUtil::specialchars($author).' · '.StringUtil::specialchars($license));
        }

        return $options;
    }

    private static function renderOption(string $set, string $styleAttr, string $name, string $license): string
    {
        return sprintf(
            '<span class="lpv-pieceset"><span class="lpv-pieceset__preview lpv-pieceset__preview--%s"%s aria-hidden="true"></span><span class="lpv-pieceset__name">%s</span>%s</span>',
            $set,
            $styleAttr,
            $name,
            '' !== $license ? '<span class="lpv-pieceset__license">'.$license.'</span>' : '',
        );
    }
}
