<?php

declare(strict_types=1);

namespace Wiksoft\ContaoLichessPgnviewerBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * Umstellung auf Vorgaben am Startpunkt der Website (Version 1.3.0, siehe
 * Settings\ViewerSettings): Leer bedeutet seitdem "Standard (erben)".
 *
 * - Ja/Nein-Felder waren Checkboxen ('1'/''). Sie werden zur Auswahl
 *   Standard/Ja/Nein (''/'y'/'n'). Entspricht der Wert dem bisherigen
 *   Standard, wird er zu '' (erbt), sonst zu 'y' bzw. 'n'.
 * - Auswahllisten, Animationsdauer, Feldfarben und Vorlage: Werte, die dem
 *   bisherigen Standard entsprechen, werden zu ''.
 *
 * Die Darstellung ändert sich dadurch nicht, solange am Startpunkt nichts
 * vorgegeben ist. Erkennungsmerkmal der alten Form: Die Spalte
 * lpv_showClocks hat noch den Standardwert '1'. Die Migration stellt alle
 * Spalten auf die neue Definition um und läuft danach nicht mehr.
 */
class ViewerDefaultsMigration extends AbstractMigration
{
    /**
     * Tabelle => Typ der Datensätze
     */
    private const TABLES = [
        'tl_content' => 'lichessPgnviewer',
        'tl_module' => 'lichessPgnviewerReader',
    ];

    /**
     * Checkbox-Felder => bisheriger Standard ('1' = an, '' = aus)
     */
    private const BOOL_FIELDS = [
        'lpv_showClocks' => '1',
        'lpv_showControls' => '1',
        'lpv_scrollToMove' => '1',
        'lpv_keyboardToMove' => '1',
        'lpv_showGameInfo' => '1',
        'lpv_drawArrows' => '1',
        'lpv_coordinates' => '1',
        'lpv_highlightLastMove' => '1',
        'lpv_highlightCheck' => '1',
        'lpv_blockTouchScroll' => '',
        'lpv_menuGetPgn' => '1',
        'lpv_menuPractice' => '1',
        'lpv_menuAnalysisBoard' => '1',
        'lpv_lichessLink' => '1',
    ];

    /**
     * Sonstige Felder => [neue Spaltendefinition, bisheriger Standard]
     */
    private const OTHER_FIELDS = [
        'lpv_showPlayers' => ["VARCHAR(1) NOT NULL DEFAULT ''", 'a'],
        'lpv_showMoves' => ["VARCHAR(1) NOT NULL DEFAULT ''", 'r'],
        'lpv_movesLayout' => ["VARCHAR(1) NOT NULL DEFAULT ''", 'c'],
        'lpv_pieceSet' => ["VARCHAR(32) NOT NULL DEFAULT ''", 'cburnett'],
        'lpv_animationDuration' => ["VARCHAR(5) NOT NULL DEFAULT ''", '250'],
        'lpv_squareLightColorHex' => ["VARCHAR(6) NOT NULL DEFAULT ''", 'f0d9b5'],
        'lpv_squareDarkColorHex' => ["VARCHAR(6) NOT NULL DEFAULT ''", 'b58863'],
    ];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function getName(): string
    {
        return 'lichess PGN-Viewer: Einstellungen auf "Standard" (Vorgaben am Startpunkt) umstellen';
    }

    public function shouldRun(): bool
    {
        return [] !== $this->getPendingTables();
    }

    public function run(): MigrationResult
    {
        $messages = [];

        foreach ($this->getPendingTables() as $table => $columns) {
            $type = self::TABLES[$table];

            // Spalten zuerst umstellen (z. B. smallint -> varchar für die Animationsdauer)
            foreach (self::BOOL_FIELDS as $field => $default) {
                if (isset($columns[strtolower($field)])) {
                    $this->connection->executeStatement("ALTER TABLE $table MODIFY $field CHAR(1) NOT NULL DEFAULT ''");
                }
            }

            foreach (self::OTHER_FIELDS as $field => [$definition]) {
                if (isset($columns[strtolower($field)])) {
                    $this->connection->executeStatement("ALTER TABLE $table MODIFY $field $definition");
                }
            }

            if ('tl_content' === $table && isset($columns['lpv_template'])) {
                $this->connection->executeStatement("ALTER TABLE $table MODIFY lpv_template VARCHAR(64) NOT NULL DEFAULT ''");
            }

            // Werte umstellen, nur bei Datensätzen des Viewers
            $set = [];

            foreach (self::BOOL_FIELDS as $field => $default) {
                if (!isset($columns[strtolower($field)])) {
                    continue;
                }

                $set[] = '1' === $default
                    ? "$field = IF($field = '1', '', 'n')"
                    : "$field = IF($field = '1', 'y', '')";
            }

            foreach (self::OTHER_FIELDS as $field => [, $default]) {
                if (isset($columns[strtolower($field)])) {
                    $set[] = "$field = IF(LOWER($field) = '$default', '', $field)";
                }
            }

            if ('tl_content' === $table && isset($columns['lpv_template'])) {
                $set[] = "lpv_template = IF(lpv_template = 'ce_lichessPgnviewer', '', lpv_template)";
            }

            $count = $this->connection->executeStatement(
                "UPDATE $table SET ".implode(', ', $set).' WHERE type = ?',
                [$type],
            );

            // Andere Datensätze (andere Elementtypen) auf leer setzen
            $reset = array_map(
                static fn (string $field): string => "$field = ''",
                array_values(array_filter(
                    [...array_keys(self::BOOL_FIELDS), ...array_keys(self::OTHER_FIELDS)],
                    static fn (string $field): bool => isset($columns[strtolower($field)]),
                )),
            );

            $this->connection->executeStatement("UPDATE $table SET ".implode(', ', $reset).' WHERE type <> ?', [$type]);

            $messages[] = sprintf('%s: %d Datensätze umgestellt', $table, $count);
        }

        return $this->createResult(true, implode('; ', $messages).'.');
    }

    /**
     * Tabellen, deren Spalte lpv_showClocks noch die alte Definition
     * (Standard '1') hat, mit ihren Spalten (Namen in Kleinbuchstaben).
     *
     * @return array<string, array<string, mixed>>
     */
    private function getPendingTables(): array
    {
        $schemaManager = $this->connection->createSchemaManager();
        $pending = [];

        foreach (array_keys(self::TABLES) as $table) {
            if (!$schemaManager->tablesExist([$table])) {
                continue;
            }

            $columns = $schemaManager->listTableColumns($table);

            // MariaDB liefert den Standardwert je nach Version mit Anführungszeichen
            if (isset($columns['lpv_showclocks']) && '1' === trim((string) $columns['lpv_showclocks']->getDefault(), "'")) {
                $pending[$table] = $columns;
            }
        }

        return $pending;
    }
}
