<?php

declare(strict_types=1);

namespace Wiksoft\ContaoLichessPgnviewerBundle\EventListener;

use Contao\System;

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
 *
 * Nachtrag: Die DCA wird auch im Frontend geladen. Deshalb prüft der Hook
 * zusätzlich per ScopeMatcher, ob es eine Backend-Anfrage ist.
 */
class LoadDataContainerListener
{
    public function onLoadDataContainer(string $table): void
    {
        if (!\in_array($table, ['tl_content', 'tl_module', 'tl_page'], true)) {
            return;
        }

        // Die DCA wird auch im Frontend geladen (z. B. beim Rendern von
        // Inhaltselementen) - die Backend-Dateien dort nicht einbinden.
        $container = System::getContainer();
        $request = $container->get('request_stack')->getCurrentRequest();

        if (!$request || !$container->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            return;
        }

        $GLOBALS['TL_CSS'][] = 'bundles/wiksoftcontaolichesspgnviewer/backend.css';

        // Vorschau der Figurensätze: Feldfarben sofort übernehmen (siehe backend.js)
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/wiksoftcontaolichesspgnviewer/backend.js';
    }
}
