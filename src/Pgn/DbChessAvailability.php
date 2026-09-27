<?php

declare(strict_types=1);

namespace Wiksoft\ContaoLichessPgnviewerBundle\Pgn;

/**
 * wiksoft/contao-dbchess-bundle ist für dieses Bundle nur ein optionaler "suggest"
 * (siehe composer.json), keine feste Abhängigkeit. Die Quelle "Interne
 * Datenbank" darf im Content-Element (tl_content.lpv_source) und beim
 * Rendern nur verwendet werden, wenn das Bundle tatsächlich installiert ist.
 *
 * Die Prüfung erfolgt bewusst über class_exists() auf die Bundle-Klasse
 * (Composer-Autoloader) statt über die Contao-Kernel-Bundle-Liste, damit sie
 * auch in DCA-Callbacks funktioniert, die ohne vollständig gebooteten Kernel
 * laufen können.
 */
final class DbChessAvailability
{
    public static function isInstalled(): bool
    {
        return class_exists(\Wiksoft\DbChessBundle\WiksoftDbChessBundle::class);
    }
}
