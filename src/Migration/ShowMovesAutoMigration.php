<?php

declare(strict_types=1);

namespace Wiksoft\ContaoLichessPgnviewerBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * Die Option "Automatisch" bei "Zugliste anzeigen" (lpv_showMoves = 'a')
 * ist entfallen: "Rechts" und "Links neben dem Brett" verhalten sich
 * inzwischen genauso responsiv (siehe public/lpv.css), und das
 * Standardverhalten von "Automatisch" im lichess-pgn-viewer ist "Zugliste
 * rechts". Bestehende Einträge werden daher auf 'r' umgestellt - im
 * Inhaltselement und im Reader-Modul.
 */
class ShowMovesAutoMigration extends AbstractMigration
{
    private const TABLES = ['tl_content', 'tl_module'];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function getName(): string
    {
        return 'lichess PGN-Viewer: Zugliste "Automatisch" -> "Rechts"';
    }

    public function shouldRun(): bool
    {
        foreach ($this->getTables() as $table) {
            $count = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM $table WHERE lpv_showMoves = 'a'");

            if ($count > 0) {
                return true;
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        $total = 0;

        foreach ($this->getTables() as $table) {
            $total += (int) $this->connection->executeStatement("UPDATE $table SET lpv_showMoves = 'r' WHERE lpv_showMoves = 'a'");
        }

        return $this->createResult(true, sprintf('%d Einträge von "Automatisch" auf "Rechts" umgestellt.', $total));
    }

    /**
     * Nur Tabellen, in denen die Spalte schon existiert (bei einer
     * Neuinstallation legt erst das Schema-Update sie an).
     *
     * @return list<string>
     */
    private function getTables(): array
    {
        $schemaManager = $this->connection->createSchemaManager();
        $tables = [];

        foreach (self::TABLES as $table) {
            if (!$schemaManager->tablesExist([$table])) {
                continue;
            }

            if (isset($schemaManager->listTableColumns($table)['lpv_showmoves'])) {
                $tables[] = $table;
            }
        }

        return $tables;
    }
}
