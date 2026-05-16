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
            '2026_05_10_190841_02_create_elements_table',
            '2026_05_10_190841_03_create_element_assets_table',
            '2026_05_10_190841_04_add_container_elements_to_layouts_table',
            '2026_05_10_190841_05_create_layout_presets_table',
        ];
    }
}
