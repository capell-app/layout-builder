<?php

declare(strict_types=1);

use Capell\AccessGate\Tests\TestCase;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

uses(TestCase::class);

it('runs the access gate core migrations', function (): void {
    expect(Schema::hasTable('access_gate_areas'))->toBeTrue()
        ->and(Schema::hasTable('access_gate_registrations'))->toBeTrue()
        ->and(Schema::hasTable('access_gate_grants'))->toBeTrue()
        ->and(Schema::hasTable('access_gate_claim_tokens'))->toBeTrue()
        ->and(Schema::hasTable('access_gate_browser_tokens'))->toBeTrue()
        ->and(Schema::hasTable('access_gate_events'))->toBeTrue();
});

it('creates access gate tables on the configured database connection', function (): void {
    Config::set('database.connections.access_gate_testing', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);

    Config::set('access-gate.connection', 'access_gate_testing');

    foreach (accessGateMigrationFiles() as $migrationFile) {
        /** @var Migration $migration */
        $migration = require $migrationFile;
        $migration->up();
    }

    expect(Schema::connection('access_gate_testing')->hasTable('access_gate_areas'))->toBeTrue()
        ->and(Schema::connection('access_gate_testing')->hasTable('access_gate_registrations'))->toBeTrue()
        ->and(Schema::connection('access_gate_testing')->hasTable('access_gate_grants'))->toBeTrue()
        ->and(Schema::connection('access_gate_testing')->hasTable('access_gate_claim_tokens'))->toBeTrue()
        ->and(Schema::connection('access_gate_testing')->hasTable('access_gate_browser_tokens'))->toBeTrue()
        ->and(Schema::connection('access_gate_testing')->hasTable('access_gate_events'))->toBeTrue();
});

it('includes registration columns needed for policy-safe email deduplication', function (): void {
    expect(Schema::hasColumn('access_gate_registrations', 'email'))->toBeTrue()
        ->and(Schema::hasColumn('access_gate_registrations', 'email_normalized'))->toBeTrue()
        ->and(Schema::hasColumn('access_gate_registrations', 'single_registration_key'))->toBeTrue()
        ->and(Schema::hasColumn('access_gate_registrations', 'field_values'))->toBeTrue();
});

/**
 * @return list<string>
 */
function accessGateMigrationFiles(): array
{
    return [
        __DIR__ . '/../../database/migrations/2026_05_08_000001_create_access_gate_areas_table.php',
        __DIR__ . '/../../database/migrations/2026_05_08_000002_create_access_gate_registrations_table.php',
        __DIR__ . '/../../database/migrations/2026_05_08_000003_create_access_gate_grants_table.php',
        __DIR__ . '/../../database/migrations/2026_05_08_000004_create_access_gate_claim_tokens_table.php',
        __DIR__ . '/../../database/migrations/2026_05_08_000005_create_access_gate_browser_tokens_table.php',
        __DIR__ . '/../../database/migrations/2026_05_08_000006_create_access_gate_events_table.php',
    ];
}
