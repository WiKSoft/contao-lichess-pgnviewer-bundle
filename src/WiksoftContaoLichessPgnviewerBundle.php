<?php

declare(strict_types=1);

namespace Wiksoft\ContaoLichessPgnviewerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class WiksoftContaoLichessPgnviewerBundle extends Bundle
{
    /**
     * Contao-Ressourcen (contao/config, contao/dca, contao/languages,
     * contao/templates) liegen auf Root-Ebene des Pakets, NICHT unter src/.
     * Ohne diesen Override würde Symfony den Bundle-Pfad aus dem Verzeichnis
     * der Bundle-Klassendatei ableiten (also "src/") und der contao/-Ordner
     * würde nie gefunden werden (siehe wiksoft/pgn4web-bundle und
     * wiksoft/dbchess-bundle, die denselben Aufbau verwenden).
     */
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
