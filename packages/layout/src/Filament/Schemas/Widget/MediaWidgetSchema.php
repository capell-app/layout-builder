<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Widget;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Layout\Filament\Components\Forms\SpacingSelect;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetAssetsRepeater;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetDisplaySection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Capell\Layout\Filament\Schemas\AbstractWidgetSchema;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class MediaWidgetSchema extends AbstractWidgetSchema
{
    public static function make(Schema $schema): array
    {
        $operation = $schema->getOperation();

        return [
            ...match ($operation) {
                'create', 'createOption', 'replicate' => self::getCreateSchema($schema),
                'editOption' => self::getEditOptionSchema($schema),
                default => self::getEditFormSchema($schema),
            },
        ];
    }

    protected static function getCreateSchema(Schema $schema): array
    {
        return [
            WidgetAssetsRepeater::make($schema),
        ];
    }

    protected static function getEditFormSchema(Schema $schema): array
    {
        return [
            FixedWidthSidebar::make()
                ->mainSchema([
                    Section::make(__('capell-admin::generic.widget_assets'))
                        ->description(__('capell-admin::generic.widget_assets_info'))
                        ->compact()
                        ->schema([
                            WidgetAssetsRepeater::make($schema)
                                ->hiddenLabel(),
                        ]),
                ])
                ->sidebarSchema([
                    Section::make()
                        ->columns(1)
                        ->schema(WidgetSettingsSchema::make($schema)),
                ]),
            self::getTabs(),
        ];
    }

    protected static function getEditOptionSchema(Schema $schema): array
    {
        return [
            WidgetAssetsRepeater::make($schema),
        ];
    }

    protected static function getTabs(): Tabs
    {
        return Tabs::make('tabs')
            ->visibleOn(['edit', 'editOption'])
            ->columnSpanFull()
            ->tabs([
                WidgetDisplayTab::make([
                    Group::make()
                        ->statePath('meta')
                        ->columns()
                        ->schema([
                            WidgetDisplaySection::make([
                                SpacingSelect::make('spacing'),
                            ]),
                            WidgetComponentFilesSection::make(),
                        ]),
                ]),
                WidgetAdminTab::make(),
            ]);
    }
}
