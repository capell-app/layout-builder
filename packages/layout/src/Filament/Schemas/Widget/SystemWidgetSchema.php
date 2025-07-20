<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Widget;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
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

class SystemWidgetSchema extends AbstractWidgetSchema
{
    public static function make(Schema $schema): array
    {
        $operation = $schema->getOperation();

        return match ($operation) {
            'create', 'createOption', 'replicate' => [
                WidgetTranslationsRepeater::make($schema)
                    ->section(fn (string $operation): bool => $operation === 'create'),
                ...self::getFilesSchema(),
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
                            ->schema(WidgetSettingsSchema::make($schema)),
                    ]),
                Tabs::make('tabs')
                    ->columnSpanFull()
                    ->tabs([
                        WidgetDisplayTab::make([
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
            Group::make()
                ->statePath('meta')
                ->columns()
                ->schema([
                    WidgetDisplaySection::make(),
                    WidgetComponentFilesSection::make(),
                ]),
        ];
    }
}
