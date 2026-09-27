<?php

declare(strict_types=1);

namespace Wiksoft\ContaoLichessPgnviewerBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Wiksoft\ContaoLichessPgnviewerBundle\WiksoftContaoLichessPgnviewerBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        // wiksoft/contao-dbchess-bundle ist nur ein optionaler "suggest" (siehe
        // composer.json), nicht als require verankert - die Datenbank als
        // PGN-Quelle ist im Content-Element nur wählbar, wenn dieses Bundle
        // tatsächlich installiert ist (siehe DbChessAvailability). Damit die
        // Ladereihenfolge stimmt, falls es installiert ist, wird die Klasse
        // hier nur bei Verfügbarkeit als "loadAfter" eingetragen.
        $loadAfter = [ContaoCoreBundle::class];

        if (class_exists(\Wiksoft\DbChessBundle\WiksoftDbChessBundle::class)) {
            $loadAfter[] = \Wiksoft\DbChessBundle\WiksoftDbChessBundle::class;
        }

        return [
            BundleConfig::create(WiksoftContaoLichessPgnviewerBundle::class)
                ->setLoadAfter($loadAfter),
        ];
    }
}
