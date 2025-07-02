<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Widget;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetSettingsTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetDisplaySection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetTranslationsRepeater;
use Filament\Forms;

class SystemWidgetSchema extends AbstractWidgetSchema
{
    public static function make(Forms\Form $form): array
    {
        $operation = $form->getOperation();

        return match ($operation) {
            'create', 'createOption', 'replicate' => [
                WidgetTranslationsRepeater::make($form),
                ...self::getFilesSchema(),
            ],
            default => [
                FixedWidthSidebar::make()
                    ->mainSchema([
                        WidgetTranslationsRepeater::make($form),
                    ])
                    ->sidebarSchema([
                        Forms\Components\Section::make()
                            ->columns(1)
                            ->schema(WidgetSettingsSchema::make($form)),
                    ]),
                Forms\Components\Tabs::make('tabs')
                    ->columnSpanFull()
                    ->tabs([
                        WidgetSettingsTab::make([
                            ...self::getFilesSchema(),
                        ]),
                        WidgetAdminTab::make(),
                    ]),
            ],
        };
    }

    protected static function getFilesSchema(): array
    {
        return [
            Forms\Components\Group::make()
                ->statePath('meta')
                ->columns()
                ->schema([
                    WidgetDisplaySection::make(),
                    WidgetComponentFilesSection::make(),
                ]),
        ];
    }
}
