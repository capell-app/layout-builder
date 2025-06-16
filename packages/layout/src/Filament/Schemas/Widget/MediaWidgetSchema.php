<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Widget;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetSettingsTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetAssetsRepeater;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetDisplaySection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Filament\Forms;

class MediaWidgetSchema extends AbstractWidgetSchema
{
    public static function make(Forms\Form $form): array
    {
        $operation = $form->getOperation();

        return [
            ...match ($operation) {
                'create', 'createOption', 'replicate' => self::getCreateSchema($form),
                'editOption' => self::getEditOptionSchema($form),
                default => self::getEditFormSchema($form),
            },
        ];
    }

    private static function getCreateSchema(Forms\Form $form): array
    {
        return [
            WidgetAssetsRepeater::make($form),
        ];
    }

    private static function getEditFormSchema(Forms\Form $form): array
    {
        return [
            FixedWidthSidebar::make()
                ->mainSchema([
                    Forms\Components\Section::make(__('capell-admin::generic.widget_resources'))
                        ->description(__('capell-admin::generic.widget_resources_info'))
                        ->compact()
                        ->schema([
                            WidgetAssetsRepeater::make($form)
                                ->hiddenLabel(),
                        ]),
                ])
                ->sidebarSchema([
                    Forms\Components\Section::make()
                        ->columns(1)
                        ->schema(WidgetSettingsSchema::make($form)),
                ]),
            self::getTabs(),
        ];
    }

    private static function getEditOptionSchema(Forms\Form $form): array
    {
        return [
            WidgetAssetsRepeater::make($form),
        ];
    }

    private static function getTabs(): Forms\Components\Tabs
    {
        return Forms\Components\Tabs::make('tabs')
            ->visibleOn(['edit', 'editOption'])
            ->columnSpanFull()
            ->tabs([
                WidgetSettingsTab::make([
                    Forms\Components\Group::make()
                        ->statePath('meta')
                        ->columns()
                        ->schema([
                            WidgetDisplaySection::make(),
                            WidgetComponentFilesSection::make(),
                        ]),
                ]),
                WidgetAdminTab::make(),
            ]);
    }
}
