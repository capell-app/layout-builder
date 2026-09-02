<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Support\CapellLayoutBuilderManager;
use Illuminate\Support\Facades\Schema;

it('registers package migrations in the layout builder manager', function (): void {
    // Compare against capell.json rather than a hardcoded list. There used to
    // be a third copy of these filenames here, so THREE places had to agree:
    // the manifest, the manager, and this test. They stopped agreeing -
    // 2026_09_01_000001_add_shadowed_by_workspace_id_to_widgets_table was
    // added to capell.json but not to the manager, and the manager list is
    // what LayoutBuilderServiceProvider hands to ->hasMigrations(). The
    // migration therefore never ran on any install, `widgets` kept
    // `workspace_id` without `shadowed_by_workspace_id`, and
    // WorkspaceContextScope throws on exactly that combination.
    //
    // A hardcoded list here could not catch that: it would simply have been
    // updated alongside whichever copy someone remembered. Deriving the
    // expectation from the manifest makes the manager the thing under test.
    $manifest = json_decode(
        (string) file_get_contents(dirname(__DIR__, 2) . '/capell.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    if (! is_array($manifest)) {
        throw new RuntimeException('capell.json did not decode to an object.');
    }

    $declared = [];

    foreach ((array) ($manifest['contributes'] ?? []) as $contribution) {
        if (is_array($contribution) && ($contribution['type'] ?? null) === 'migration') {
            foreach ((array) ($contribution['migrationFiles'] ?? []) as $migrationFile) {
                $declared[] = $migrationFile;
            }
        }
    }

    expect($declared)->not->toBeEmpty()
        ->and(CapellLayoutBuilderManager::getMigrations())->toBe($declared);
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
