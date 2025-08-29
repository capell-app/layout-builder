<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Schemas\Types;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\Media\MediaLibraryFileUpload;
use Capell\Layout\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Layout\Filament\Components\Forms\Widget\CreateWidgetDetailsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetDisplaySection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetTranslationsRepeater;
use Capell\Layout\Livewire\Filament\WidgetAssetsTable;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Override;

class AssetsWidgetSchema extends DefaultWidgetSchema
{
    #[Override]
    public static function make(Schema $schema): array
    {
        $operation = $schema->getOperation();

        return [
            ...match ($operation) {
                'createOption', 'editOption', 'replicate' => static::getOptionSchema($schema),
                default => static::getEditFormSchema($schema),
            },
        ];
    }

    protected static function getOptionSchema(Schema $schema): array
    {
        return [
            CreateWidgetDetailsSchema::make($schema),
            Tabs::make()
                ->columnSpanFull()
                ->tabs([
                    static::getAssetsTab($schema),
                    static::getContentTab($schema),
                    static::getSettingsTab($schema),
                    static::getAdminTab($schema),
                ]),
        ];
    }

    protected static function getEditFormSchema(Schema $schema): array
    {
        return [
            CreateWidgetDetailsSchema::make($schema),
            FixedWidthSidebar::make()
                ->mainSchema(static::getMainSchema($schema))
                ->sidebarSchema(static::getSidebarSchema($schema)),
            Tabs::make()
                ->columnSpanFull()
                ->tabs([
                    static::getContentTab($schema),
                    static::getSettingsTab($schema),
                    static::getAdminTab($schema),
                ]),
        ];
    }

    protected static function getMainSchema(Schema $schema): array
    {
        return [
            Livewire::make(WidgetAssetsTable::class, ['schema' => $schema])
                ->key('widget-assets-table'),
        ];
    }

    protected static function getSidebarSchema(Schema $schema): array
    {
        return [
            Section::make()
                ->columns(1)
                ->schema(WidgetSettingsSchema::make($schema)),
        ];
    }

    protected static function getAssetsTab(Schema $schema): Tab
    {
        return Tab::make(__('capell-admin::tab.assets'))
            ->schema([
                Livewire::make(WidgetAssetsTable::class, ['schema' => $schema])
                    ->key('widget-assets-table'),
            ]);
    }

    protected static function getContentTab(Schema $schema): Tab
    {
        return Tab::make(__('capell-admin::tab.content'))
            ->schema([
                WidgetTranslationsRepeater::make($schema),
            ]);
    }

    protected static function getAdminTab(Schema $schema): Tab
    {
        return WidgetAdminTab::make();
    }

    protected static function getSettingsTab(Schema $schema): Tab
    {
        return WidgetDisplayTab::make([
            Grid::make()
                ->statePath('meta')
                ->schema([
                    MediaLibraryFileUpload::make('image'),
                    WidgetDisplaySection::make([
                        ColorSchemeComponent::make('color_scheme'),
                    ]),
                    WidgetComponentFilesSection::make(),
                ]),
        ]);
    }
}
