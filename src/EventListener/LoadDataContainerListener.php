<?php

declare(strict_types=1);

namespace Wiksoft\ContaoLichessPgnviewerBundle\EventListener;

/**
 * Registrierung erfolgt klassisch über $GLOBALS['TL_HOOKS']['loadDataContainer']
 * in contao/config/config.php (analog zum Stil von
 * wiksoft/pgn4web-bundle\EventListener\Pgn4webInsertTag), NICHT über ein
 * #[AsHook]-Attribut.
 *
 * Bindet backend.css (siehe public/backend.css) nur dann ein, wenn die
 * tl_content-DCA geladen wird - also nur im Backend, beim Bearbeiten von
 * Inhaltselementen. $GLOBALS['TL_CSS'] wird zwar sowohl im Frontend
 * (Contao\CoreBundle\ContentComposition\ContentCompositionBuilder) als auch
 * im Backend (Contao\BackendTemplate::compile()) ausgewertet - der frühere
 * Versuch, das per "TL_MODE === 'BE'"-Prüfung in config.php auf das Backend
 * zu beschränken, griff nicht, weil die TL_MODE-Konstante in dieser
 * Contao-Version nicht mehr existiert (config.php lädt dadurch faktisch
 * immer im Frontend-Zweig, nie im vermeintlichen Backend-Zweig). Der
 * loadDataContainer-Hook feuert dagegen zuverlässig nur dann, wenn die
 * tl_content-DCA tatsächlich geladen wird (Backend-Bearbeitung), und ist
 * damit der korrekte Ersatz. Für das Frontend-Modul lichessPgnviewerReader
 * gilt dasselbe mit der tl_module-DCA.
 */
class LoadDataContainerListener
{
    public function onLoadDataContainer(string $table): void
    {
        if (!\in_array($table, ['tl_content', 'tl_module'], true)) {
            return;
        }

        $GLOBALS['TL_CSS'][] = 'bundles/wiksoftcontaolichesspgnviewer/backend.css';
    }
}
