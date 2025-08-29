<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Schemas\Types;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\Navigation\NavigationSelect;
use Capell\Layout\Filament\Components\Forms\Widget\CreateWidgetDetailsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetDisplaySection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetTranslationsRepeater;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Override;

class NavigationWidgetSchema extends DefaultWidgetSchema
{
    #[Override]
    public static function make(Schema $schema): array
    {
        $operation = $schema->getOperation();

        return match ($operation) {
            'createOption' => static::getCreateOptionSchema($schema),
            'editOption', 'replicate' => static::getEditOptionSchema($schema),
            default => static::getFormSchema($schema),
        };
    }

    protected static function getCreateOptionSchema(Schema $schema): array
    {
        return [
            CreateWidgetDetailsSchema::make($schema),
            Section::make()
                ->schema([static::navigationSelect()]),
            WidgetTranslationsRepeater::make($schema)
                ->section(),
        ];
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

    protected static function getEditOptionSchema(Schema $schema): array
    {
        return [
            static::navigationSelect(),
            WidgetTranslationsRepeater::make($schema),
        ];
    }

    protected static function getFormSchema(Schema $schema): array
    {
        return [
            CreateWidgetDetailsSchema::make($schema),
            FixedWidthSidebar::make()
                ->mainSchema([
                    WidgetTranslationsRepeater::make($schema)
                        ->section(),
                ])
                ->sidebarSchema([
                    Section::make()
                        ->columns(1)
                        ->schema(WidgetSettingsSchema::make($schema, [static::navigationSelect()])),
                ]),
            Tabs::make()
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
        ];
    }
}
