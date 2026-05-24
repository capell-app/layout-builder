<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

final class CapellLayoutBuilderManager
{
    /**
     * @return list<string>
     */
    public static function getMigrations(): array
    {
        return [
            '2026_05_10_190841_01_create_layouts_table',
            '2026_05_10_190841_02_create_widgets_table',
            '2026_05_10_190841_03_create_widget_assets_table',
            '2026_05_10_190841_04_create_widget_blocks_table',
            '2026_05_10_190841_05_add_container_widgets_to_layouts_table',
            '2026_05_10_190841_06_create_layout_presets_table',
        ];
    }
}
