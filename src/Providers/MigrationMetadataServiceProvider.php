<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Providers;

use Capell\Core\Support\Migration\MigrationRootRegistry;
use Illuminate\Support\ServiceProvider;
use Override;

final class MigrationMetadataServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {

        $this->callAfterResolving(MigrationRootRegistry::class, static function (MigrationRootRegistry $registry): void {
            $registry->register('capell-app/layout-builder', __DIR__ . '/../../database/migrations', [
                '2026_05_10_190841_02_create_widgets_table' => 'widgets',
                '2026_05_10_190841_03_create_widget_assets_table' => 'widget_assets',
                '2026_05_10_190841_04_create_widget_widgets_table' => 'widget_widgets',
                '2026_05_10_190841_06_create_layout_presets_table' => 'layout_presets',
                '2026_06_07_000001_create_layout_bulk_change_tables' => ['layout_bulk_change_runs', 'layout_bulk_change_results'],
                '2026_07_09_000001_create_public_widget_snapshots_table' => 'public_widget_snapshots',
                '2026_07_10_000002_create_layout_preset_usages_table' => 'layout_preset_usages',
                '2026_07_10_000003_create_layout_preset_sync_runs_table' => ['layout_preset_sync_runs', 'layout_preset_sync_results'],
            ]);
        });
    }
}
