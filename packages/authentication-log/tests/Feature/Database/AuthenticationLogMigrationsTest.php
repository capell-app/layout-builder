<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('loads the package-owned authentication log table migration', function (): void {
    $tableName = config('authentication-log.table_name', 'authentication_log');

    expect(Schema::hasTable($tableName))->toBeTrue()
        ->and(Schema::hasColumn($tableName, 'last_seen_at'))->toBeTrue()
        ->and(Schema::hasColumn($tableName, 'device_id'))->toBeTrue()
        ->and(Schema::hasColumn($tableName, 'device_name'))->toBeTrue()
        ->and(Schema::hasColumn($tableName, 'is_trusted'))->toBeTrue()
        ->and(Schema::hasColumn($tableName, 'last_activity_at'))->toBeTrue()
        ->and(Schema::hasColumn($tableName, 'is_suspicious'))->toBeTrue()
        ->and(Schema::hasColumn($tableName, 'suspicious_reason'))->toBeTrue();
});

it('creates the final authentication log schema with a custom table name', function (): void {
    $tableName = 'custom_authentication_log';

    config()->set('authentication-log.table_name', $tableName);

    Schema::dropIfExists('authentication_log');
    Schema::dropIfExists($tableName);

    $migrationPath = dirname(__DIR__, 3) . '/database/migrations';
    $createMigration = include $migrationPath . '/create_authentication_log_table.php';

    $createMigration->up();

    expect(Schema::hasTable($tableName))->toBeTrue()
        ->and(Schema::hasColumn($tableName, 'last_seen_at'))->toBeTrue()
        ->and(Schema::hasIndex($tableName, 'authenticatable_login_at_index'))->toBeTrue();

    Schema::dropIfExists($tableName);
});
