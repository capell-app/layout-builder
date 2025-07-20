<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Widget;

use Capell\Admin\Actions\FixCuratorMetaDataAction;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\Media\ImageMediaPicker;
use Capell\Layout\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetAssetsRepeater;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetDisplaySection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetTranslationsRepeater;
use Capell\Layout\Filament\Schemas\AbstractWidgetSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class AssetsWidgetSchema extends AbstractWidgetSchema
{
    public static function make(Schema $schema): array
    {
        $operation = $schema->getOperation();

        return [
            ...match ($operation) {
                'create', 'createOption', 'replicate' => self::getCreateOptionSchema($schema),
                default => self::getEditFormSchema($schema),
            },
        ];
    }

    protected static function getCreateOptionSchema(Schema $schema): array
    {
        return [
            WidgetAssetsRepeater::make($schema),
        ];
    }

    protected static function getEditFormSchema(Schema $schema): array
    {
        return [
            FixedWidthSidebar::make()
                ->mainSchema(self::getMainSchema($schema))
                ->sidebarSchema(self::getSidebarSchema($schema)),
            self::getTabs($schema),
        ];
    }

    protected static function getMainSchema(Schema $schema): array
    {
        return [
            Section::make(__('capell-admin::generic.widget_assets'))
                ->description(__('capell-admin::generic.widget_assets_info'))
                ->compact()
                ->schema([
                    WidgetAssetsRepeater::make($schema)
                        ->hiddenLabel(),
                ]),
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

    protected static function getTabs(Schema $schema): Tabs
    {
        return Tabs::make('tabs')
            ->columnSpanFull()
            ->tabs([
                static::getContentTab($schema),
                static::getSettingsTab($schema),
                static::getAdminTab($schema),
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
                ->mutateDehydratedStateUsing(function (array $state): array {
                    if (! empty($state['background_image_id'])) {
                        $state['background_image_id'] = FixCuratorMetaDataAction::run($state['background_image_id']);
                    }

                    if (! empty($state['image_id'])) {
                        $state['image_id'] = FixCuratorMetaDataAction::run($state['image_id']);
                    }

                    return $state;
                })
                ->schema([
                    ImageMediaPicker::make('image_id'),
                    WidgetDisplaySection::make([
                        ColorSchemeComponent::make('color_scheme'),
                    ]),
                    WidgetComponentFilesSection::make(),
                ]),
        ]);
    }
}
