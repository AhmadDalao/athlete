<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MySqlMigrationIdentifierTest extends TestCase
{
    private const MIGRATION = __DIR__.'/../../database/migrations/2026_08_11_000000_expand_throughline_platform.php';

    #[Test]
    public function expansion_migration_names_every_foreign_key_explicitly(): void
    {
        $source = $this->upMigrationSource();
        preg_match_all('/\$table->foreignId\([^;]+;/s', $source, $foreignKeys);

        $this->assertNotEmpty($foreignKeys[0]);

        foreach ($foreignKeys[0] as $foreignKey) {
            $this->assertStringContainsString(
                'indexName:',
                $foreignKey,
                "Foreign keys in the expansion migration need explicit MySQL-safe names:\n{$foreignKey}",
            );
        }
    }

    #[Test]
    public function expansion_migration_names_every_composite_index_explicitly(): void
    {
        $source = $this->upMigrationSource();
        preg_match_all('/\$table->(?:unique|index)\(\[[^;]+;/s', $source, $indexes);

        $this->assertNotEmpty($indexes[0]);

        foreach ($indexes[0] as $index) {
            $this->assertMatchesRegularExpression(
                "/\],\s*'[^']+'\)/",
                $index,
                "Composite indexes in the expansion migration need explicit MySQL-safe names:\n{$index}",
            );
        }
    }

    #[Test]
    public function every_explicit_mysql_identifier_fits_the_server_limit(): void
    {
        $source = file_get_contents(self::MIGRATION);
        $this->assertIsString($source);

        preg_match_all("/indexName:\s*'([^']+)'/", $source, $foreignKeyNames);
        preg_match_all("/\$table->(?:unique|index)\([^;]+,\s*'([^']+)'\)/", $source, $indexNames);
        preg_match_all("/\$table->(?:dropUnique|dropForeign)\(\s*'([^']+)'\s*\)/", $source, $droppedNames);

        $dynamicOrganizationKeys = array_map(
            fn (string $table): string => $table.'_org_fk',
            ['athlete_invitations', 'coach_athlete_assignments', 'training_programs', 'training_sessions', 'workout_logs', 'progress_entries', 'audit_logs', 'email_logs'],
        );

        $identifiers = [
            ...$foreignKeyNames[1],
            ...$indexNames[1],
            ...$droppedNames[1],
            ...$dynamicOrganizationKeys,
        ];

        $this->assertNotEmpty($identifiers);

        foreach ($identifiers as $identifier) {
            $this->assertLessThanOrEqual(
                64,
                strlen($identifier),
                "MySQL identifier exceeds 64 characters: {$identifier}",
            );
        }
    }

    private function upMigrationSource(): string
    {
        $source = file_get_contents(self::MIGRATION);
        $this->assertIsString($source);

        return explode('    private function backfillExistingData()', $source, 2)[0];
    }
}
