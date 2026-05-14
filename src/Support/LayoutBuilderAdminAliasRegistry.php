<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\LayoutBuilder\Actions\BuildLayoutContentInventoryAction;
use Capell\LayoutBuilder\Data\LayoutContentGroupData;
use Capell\LayoutBuilder\Data\LayoutContentInventoryContextData;
use Capell\LayoutBuilder\Data\LayoutContentInventoryData;
use Capell\LayoutBuilder\Data\LayoutContentItemData;
use Capell\LayoutBuilder\Enums\LayoutBreakpoint;
use Capell\LayoutBuilder\Enums\LayoutBuilderEditorMode;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;

final class LayoutBuilderAdminAliasRegistry
{
    /**
     * @return array<class-string, class-string>
     */
    public static function aliases(): array
    {
        return [
            \Capell\Admin\LayoutBuilder\Actions\BuildLayoutContentInventoryAction::class => BuildLayoutContentInventoryAction::class,
            \Capell\Admin\LayoutBuilder\Data\LayoutContentGroupData::class => LayoutContentGroupData::class,
            \Capell\Admin\LayoutBuilder\Data\LayoutContentInventoryContextData::class => LayoutContentInventoryContextData::class,
            \Capell\Admin\LayoutBuilder\Data\LayoutContentInventoryData::class => LayoutContentInventoryData::class,
            \Capell\Admin\LayoutBuilder\Data\LayoutContentItemData::class => LayoutContentItemData::class,
            \Capell\Admin\LayoutBuilder\Enums\LayoutBreakpoint::class => LayoutBreakpoint::class,
            \Capell\Admin\LayoutBuilder\Enums\LayoutBuilderEditorMode::class => LayoutBuilderEditorMode::class,
            \Capell\Admin\LayoutBuilder\Livewire\Filament\LayoutBuilder::class => LayoutBuilder::class,
        ];
    }

    public static function register(): void
    {
        foreach (self::aliases() as $source => $alias) {
            if (! class_exists($source) && ! enum_exists($source)) {
                continue;
            }

            if (class_exists($alias) || enum_exists($alias)) {
                continue;
            }

            class_alias($source, $alias);
        }
    }
}
