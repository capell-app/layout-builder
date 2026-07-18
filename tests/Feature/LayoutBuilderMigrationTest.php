<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Support\CapellLayoutBuilderManager;
use Illuminate\Support\Facades\Schema;

it('registers package migrations in the layout builder manager', function (): void {
    expect(CapellLayoutBuilderManager::getMigrations())->toBe([
        '2026_05_10_190841_02_create_widgets_table',
        '2026_05_10_190841_03_create_widget_assets_table',
        '2026_05_10_190841_04_create_widget_widgets_table',
        '2026_05_10_190841_05_add_container_widgets_to_layouts_table',
        '2026_05_10_190841_06_create_layout_presets_table',
        '2026_06_07_000001_create_layout_bulk_change_tables',
        '2026_07_09_000001_create_public_widget_snapshots_table',
        '2026_07_10_000001_add_linked_preset_fields_to_layout_presets_table',
        '2026_07_10_000002_create_layout_preset_usages_table',
        '2026_07_10_000003_create_layout_preset_sync_runs_table',
    ]);
});

it('creates or recognises the existing layout builder tables', function (): void {
    expect(Schema::hasTable('layouts'))->toBeTrue()
        ->and(Schema::hasTable('widgets'))->toBeTrue()
        ->and(Schema::hasTable('widget_assets'))->toBeTrue()
        ->and(Schema::hasTable('widget_widgets'))->toBeTrue()
        ->and(Schema::hasTable('layout_presets'))->toBeTrue()
        ->and(Schema::hasTable('layout_bulk_change_runs'))->toBeTrue()
        ->and(Schema::hasTable('layout_bulk_change_results'))->toBeTrue()
        ->and(Schema::hasTable('public_widget_snapshots'))->toBeTrue()
        ->and(Schema::hasTable('layout_preset_usages'))->toBeTrue()
        ->and(Schema::hasTable('layout_preset_sync_runs'))->toBeTrue()
        ->and(Schema::hasTable('layout_preset_sync_results'))->toBeTrue()
        ->and(Schema::hasColumn('layouts', 'containers'))->toBeTrue()
        ->and(Schema::hasColumn('layouts', 'widgets'))->toBeFalse();
});

it('keeps layout builder migrations idempotent for existing core installs', function (): void {
    foreach (CapellLayoutBuilderManager::getMigrations() as $migration) {
        $instance = include dirname(__DIR__, 2) . '/database/migrations/' . $migration . '.php';

        $instance->up();
        $instance->up();
    }

    expect(Schema::hasTable('layouts'))->toBeTrue()
        ->and(Schema::hasTable('widgets'))->toBeTrue()
        ->and(Schema::hasTable('widget_assets'))->toBeTrue()
        ->and(Schema::hasTable('widget_widgets'))->toBeTrue()
        ->and(Schema::hasTable('layout_presets'))->toBeTrue()
        ->and(Schema::hasTable('layout_bulk_change_runs'))->toBeTrue()
        ->and(Schema::hasTable('layout_bulk_change_results'))->toBeTrue()
        ->and(Schema::hasTable('public_widget_snapshots'))->toBeTrue()
        ->and(Schema::hasTable('layout_preset_usages'))->toBeTrue()
        ->and(Schema::hasTable('layout_preset_sync_runs'))->toBeTrue()
        ->and(Schema::hasTable('layout_preset_sync_results'))->toBeTrue()
        ->and(Schema::hasColumn('layouts', 'containers'))->toBeTrue()
        ->and(Schema::hasColumn('layouts', 'widgets'))->toBeFalse();
});

it('reverses layout builder create-table migrations', function (string $migration, array $tables): void {
    $instance = include dirname(__DIR__, 2) . '/database/migrations/' . $migration . '.php';

    $instance->up();

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue();
    }

    $instance->down();

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeFalse();
    }
})->with([
    'layout presets' => ['2026_05_10_190841_06_create_layout_presets_table', ['layout_presets']],
    'bulk changes' => ['2026_06_07_000001_create_layout_bulk_change_tables', ['layout_bulk_change_results', 'layout_bulk_change_runs']],
]);
