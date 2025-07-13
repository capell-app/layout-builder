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
use Filament\Forms;

class NavigationWidgetSchema extends AbstractWidgetSchema
{
    public static function make(Forms\Form $form): array
    {
        $operation = $form->getOperation();

        return match ($operation) {
            'create' => [
                Forms\Components\Section::make()
                    ->schema([self::navigationSelect()]),
                WidgetTranslationsRepeater::make($form)
                    ->section(),
            ],
            'createOption', 'replicate' => [
                self::navigationSelect(),
                WidgetTranslationsRepeater::make($form),
            ],
            'editOption' => [
                self::navigationSelect(),
                WidgetTranslationsRepeater::make($form),
            ],
            default => [
                FixedWidthSidebar::make()
                    ->mainSchema([
                        WidgetTranslationsRepeater::make($form)
                            ->section(),
                    ])
                    ->sidebarSchema([
                        Forms\Components\Section::make()
                            ->columns(1)
                            ->schema(WidgetSettingsSchema::make($form, schema: [self::navigationSelect()])),
                    ]),
                Forms\Components\Tabs::make('tabs')
                    ->visibleOn(['edit', 'editOption'])
                    ->columnSpanFull()
                    ->tabs([
                        WidgetDisplayTab::make([
                            Forms\Components\Group::make()
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

    protected static function navigationSelect(): Forms\Components\Group
    {
        return Forms\Components\Group::make()
            ->statePath('meta')
            ->schema([
                NavigationSelect::make('navigation')
                    ->required(),
            ]);
    }
}
