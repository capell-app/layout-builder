<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Support\CapellLayoutBuilderManager;
use Illuminate\Support\Facades\Schema;

it('registers package migrations in the layout builder manager', function (): void {
    expect(CapellLayoutBuilderManager::getMigrations())->toBe([
        '2026_05_10_190841_01_create_layouts_table',
        '2026_05_10_190841_02_create_elements_table',
        '2026_05_10_190841_03_create_element_assets_table',
        '2026_05_10_190841_04_add_container_elements_to_layouts_table',
        '2026_05_10_190841_05_create_layout_presets_table',
    ]);
});

it('creates or recognises the existing layout builder tables', function (): void {
    expect(Schema::hasTable('layouts'))->toBeTrue()
        ->and(Schema::hasTable('elements'))->toBeTrue()
        ->and(Schema::hasTable('layout_element_assets'))->toBeTrue()
        ->and(Schema::hasTable('layout_presets'))->toBeTrue()
        ->and(Schema::hasColumns('layouts', ['containers', 'elements']))->toBeTrue();
});

it('keeps layout builder migrations idempotent for existing core installs', function (): void {
    foreach (CapellLayoutBuilderManager::getMigrations() as $migration) {
        $instance = include dirname(__DIR__, 2) . '/database/migrations/' . $migration . '.php';

        $instance->up();
        $instance->up();
    }

    expect(Schema::hasTable('layouts'))->toBeTrue()
        ->and(Schema::hasTable('elements'))->toBeTrue()
        ->and(Schema::hasTable('layout_element_assets'))->toBeTrue()
        ->and(Schema::hasTable('layout_presets'))->toBeTrue()
        ->and(Schema::hasColumns('layouts', ['containers', 'elements']))->toBeTrue();
});
