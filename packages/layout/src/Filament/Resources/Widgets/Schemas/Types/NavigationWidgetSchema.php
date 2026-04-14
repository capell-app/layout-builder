<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Schemas\Types;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\NavigationSelect;
use Capell\Layout\Filament\Components\Forms\Widget\ComponentSection;
use Capell\Layout\Filament\Components\Forms\Widget\CreateDetailsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\DisplaySection;
use Capell\Layout\Filament\Components\Forms\Widget\SettingsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Components\Forms\Widget\TranslationsRepeater;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Override;

class NavigationWidgetSchema extends DefaultWidgetSchema
{
    #[Override]
    public function make(Schema $schema): array
    {
        $operation = $schema->getOperation();

        return match ($operation) {
            'createOption' => $this->getCreateOptionSchema($schema),
            'editOption', 'replicate' => $this->getEditOptionSchema($schema),
            default => $this->getFormSchema($schema),
        };
    }

    protected function getCreateOptionSchema(Schema $schema): array
    {
        return [
            CreateDetailsSchema::make($schema),
            Section::make()
                ->schema([$this->navigationSelect()]),
            TranslationsRepeater::make($schema)
                ->contained(),
        ];
    }

    protected function navigationSelect(): Group
    {
        return Group::make()
            ->statePath('meta')
            ->schema([
                NavigationSelect::make('navigation')
                    ->required(),
            ]);
    }

    protected function getEditOptionSchema(Schema $schema): array
    {
        return [
            $this->navigationSelect(),
            TranslationsRepeater::make($schema),
        ];
    }

    protected function getFormSchema(Schema $schema): array
    {
        return [
            CreateDetailsSchema::make($schema),
            FixedWidthSidebar::make()
                ->mainSchema([
                    TranslationsRepeater::make($schema)
                        ->contained(),
                ])
                ->sidebarSchema(
                    SettingsSchema::make($schema, [$this->navigationSelect()]),
                    contained: true,
                ),
            Tabs::make()
                ->visibleOn(['edit', 'editOption'])
                ->columnSpanFull()
                ->tabs([
                    WidgetDisplayTab::make([
                        DisplaySection::make(),
                        ComponentSection::make()
                            ->statePath('meta'),
                    ]),
                    WidgetAdminTab::make(),
                ]),
        ];
    }
}
