<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Widget;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\Navigation\NavigationSelect;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetDisplaySection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetTranslationsRepeater;
use Capell\Layout\Filament\Schemas\AbstractWidgetSchema;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class NavigationWidgetSchema extends AbstractWidgetSchema
{
    public static function make(Schema $schema): array
    {
        $operation = $schema->getOperation();

        return match ($operation) {
            'create' => [
                Section::make()
                    ->schema([self::navigationSelect()]),
                WidgetTranslationsRepeater::make($schema)
                    ->section(),
            ],
            'createOption', 'replicate' => [
                self::navigationSelect(),
                WidgetTranslationsRepeater::make($schema),
            ],
            'editOption' => [
                self::navigationSelect(),
                WidgetTranslationsRepeater::make($schema),
            ],
            default => [
                FixedWidthSidebar::make()
                    ->mainSchema([
                        WidgetTranslationsRepeater::make($schema)
                            ->section(),
                    ])
                    ->sidebarSchema([
                        Section::make()
                            ->columns(1)
                            ->schema(WidgetSettingsSchema::make($schema, [self::navigationSelect()])),
                    ]),
                Tabs::make('tabs')
                    ->visibleOn(['edit', 'editOption'])
                    ->columnSpanFull()
                    ->tabs([
                        WidgetDisplayTab::make([
                            Group::make()
                                ->statePath('meta')
                                ->columns()
                                ->schema([
                                    WidgetDisplaySection::make(),
                                    WidgetComponentFilesSection::make(),
                                ]),
                        ]),
                        WidgetAdminTab::make(),
                    ]),
            ],
        };
    }

    protected static function navigationSelect(): Group
    {
        return Group::make()
            ->statePath('meta')
            ->schema([
                NavigationSelect::make('navigation')
                    ->required(),
            ]);
    }
}
