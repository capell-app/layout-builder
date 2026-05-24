<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Support\CapellLayoutBuilderManager;
use Illuminate\Support\Facades\Schema;

it('registers package migrations in the layout builder manager', function (): void {
    expect(CapellLayoutBuilderManager::getMigrations())->toBe([
        '2026_05_10_190841_01_create_layouts_table',
        '2026_05_10_190841_02_create_widgets_table',
        '2026_05_10_190841_03_create_widget_assets_table',
        '2026_05_10_190841_04_create_widget_blocks_table',
        '2026_05_10_190841_05_add_container_widgets_to_layouts_table',
        '2026_05_10_190841_06_create_layout_presets_table',
    ]);
});

it('creates or recognises the existing layout builder tables', function (): void {
    expect(Schema::hasTable('layouts'))->toBeTrue()
        ->and(Schema::hasTable('widgets'))->toBeTrue()
        ->and(Schema::hasTable('widget_assets'))->toBeTrue()
        ->and(Schema::hasTable('widget_blocks'))->toBeTrue()
        ->and(Schema::hasTable('layout_presets'))->toBeTrue()
        ->and(Schema::hasColumns('layouts', ['containers', 'widgets']))->toBeTrue();
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
        ->and(Schema::hasTable('widget_blocks'))->toBeTrue()
        ->and(Schema::hasTable('layout_presets'))->toBeTrue()
        ->and(Schema::hasColumns('layouts', ['containers', 'widgets']))->toBeTrue();
});
