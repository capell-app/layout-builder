<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\LayoutBuilder\Actions\AnalyzeLayoutDiagnosticsAction;
use Capell\LayoutBuilder\Actions\BuildLayoutContentInventoryAction;
use Capell\LayoutBuilder\Actions\Mutations\CreateLayoutFragmentAction;
use Capell\LayoutBuilder\Actions\Mutations\NormalizeLayoutBuilderStateAction;
use Capell\LayoutBuilder\Actions\Mutations\PasteLayoutFragmentAction;
use Capell\LayoutBuilder\Actions\Mutations\ReorderLayoutContainerAction;
use Capell\LayoutBuilder\Actions\Mutations\ReorderLayoutWidgetAction;
use Capell\LayoutBuilder\Actions\Mutations\ResizeLayoutContainerAction;
use Capell\LayoutBuilder\Actions\SummarizeLayoutChangesAction;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutChangeData;
use Capell\LayoutBuilder\Data\LayoutContentGroupData;
use Capell\LayoutBuilder\Data\LayoutContentInventoryContextData;
use Capell\LayoutBuilder\Data\LayoutContentInventoryData;
use Capell\LayoutBuilder\Data\LayoutContentItemData;
use Capell\LayoutBuilder\Data\LayoutDiagnosticData;
use Capell\LayoutBuilder\Data\LayoutFragmentData;
use Capell\LayoutBuilder\Data\LayoutMutationResultData;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Enums\LayoutBreakpoint;
use Capell\LayoutBuilder\Enums\LayoutBuilderEditorMode;
use Capell\LayoutBuilder\Enums\LayoutDiagnosticSeverity;
use Capell\LayoutBuilder\Filament\Extenders\Page\HeroPageSchemaExtender;
use Capell\LayoutBuilder\Filament\Resources\Layouts\LayoutResource;
use Capell\LayoutBuilder\Filament\Resources\Layouts\Schemas\Extenders\LayoutSchemaExtender;
use Capell\LayoutBuilder\Filament\Resources\Pages\Schemas\Extenders\PageSchemaExtender;
use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;

final class LayoutBuilderAdminAliasRegistry
{
    /**
     * @return array<class-string, class-string>
     */
    public static function aliases(): array
    {
        return [
            \Capell\Admin\LayoutBuilder\Actions\AnalyzeLayoutDiagnosticsAction::class => AnalyzeLayoutDiagnosticsAction::class,
            \Capell\Admin\LayoutBuilder\Actions\BuildLayoutContentInventoryAction::class => BuildLayoutContentInventoryAction::class,
            \Capell\Admin\LayoutBuilder\Actions\Mutations\CreateLayoutFragmentAction::class => CreateLayoutFragmentAction::class,
            \Capell\Admin\LayoutBuilder\Actions\Mutations\NormalizeLayoutBuilderStateAction::class => NormalizeLayoutBuilderStateAction::class,
            \Capell\Admin\LayoutBuilder\Actions\Mutations\PasteLayoutFragmentAction::class => PasteLayoutFragmentAction::class,
            \Capell\Admin\LayoutBuilder\Actions\Mutations\ReorderLayoutContainerAction::class => ReorderLayoutContainerAction::class,
            \Capell\Admin\LayoutBuilder\Actions\Mutations\ReorderLayoutWidgetAction::class => ReorderLayoutWidgetAction::class,
            \Capell\Admin\LayoutBuilder\Actions\Mutations\ResizeLayoutContainerAction::class => ResizeLayoutContainerAction::class,
            \Capell\Admin\LayoutBuilder\Actions\SummarizeLayoutChangesAction::class => SummarizeLayoutChangesAction::class,
            \Capell\Admin\LayoutBuilder\Data\LayoutBuilderStateData::class => LayoutBuilderStateData::class,
            \Capell\Admin\LayoutBuilder\Data\LayoutChangeData::class => LayoutChangeData::class,
            \Capell\Admin\LayoutBuilder\Data\LayoutContentGroupData::class => LayoutContentGroupData::class,
            \Capell\Admin\LayoutBuilder\Data\LayoutContentInventoryContextData::class => LayoutContentInventoryContextData::class,
            \Capell\Admin\LayoutBuilder\Data\LayoutContentInventoryData::class => LayoutContentInventoryData::class,
            \Capell\Admin\LayoutBuilder\Data\LayoutContentItemData::class => LayoutContentItemData::class,
            \Capell\Admin\LayoutBuilder\Data\LayoutDiagnosticData::class => LayoutDiagnosticData::class,
            \Capell\Admin\LayoutBuilder\Data\LayoutFragmentData::class => LayoutFragmentData::class,
            \Capell\Admin\LayoutBuilder\Data\LayoutMutationResultData::class => LayoutMutationResultData::class,
            \Capell\Admin\LayoutBuilder\Enums\LayoutBreakpoint::class => LayoutBreakpoint::class,
            \Capell\Admin\LayoutBuilder\Enums\LayoutBuilderEditorMode::class => LayoutBuilderEditorMode::class,
            \Capell\Admin\LayoutBuilder\Enums\LayoutDiagnosticSeverity::class => LayoutDiagnosticSeverity::class,
            \Capell\Admin\LayoutBuilder\Enums\ConfiguratorTypeEnum::class => ConfiguratorTypeEnum::class,
            \Capell\Admin\LayoutBuilder\Filament\Extenders\Page\HeroPageSchemaExtender::class => HeroPageSchemaExtender::class,
            \Capell\Admin\LayoutBuilder\Filament\Resources\Layouts\LayoutResource::class => LayoutResource::class,
            \Capell\Admin\LayoutBuilder\Filament\Resources\Layouts\Schemas\Extenders\LayoutSchemaExtender::class => LayoutSchemaExtender::class,
            \Capell\Admin\LayoutBuilder\Filament\Resources\Pages\Schemas\Extenders\PageSchemaExtender::class => PageSchemaExtender::class,
            \Capell\Admin\LayoutBuilder\Filament\Resources\Widgets\WidgetResource::class => WidgetResource::class,
            \Capell\Admin\LayoutBuilder\Support\LayoutClipboard::class => LayoutClipboard::class,
            \Capell\Admin\LayoutBuilder\Support\LayoutMutationHistory::class => LayoutMutationHistory::class,
            \Capell\Admin\LayoutBuilder\Support\LayoutPresetRepository::class => LayoutPresetRepository::class,
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
