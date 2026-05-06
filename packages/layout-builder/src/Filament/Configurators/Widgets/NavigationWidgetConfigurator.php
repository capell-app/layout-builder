<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Widgets;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\ComponentSection;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\CreateDetailsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\DisplaySection;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\SettingsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\TranslationsRepeater;
use Capell\Navigation\Filament\Components\Forms\NavigationSelect;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Override;

class NavigationWidgetConfigurator extends DefaultWidgetConfigurator
{
    #[Override]
    public function make(Schema $configurator): array
    {
        $operation = $configurator->getOperation();

        return match ($operation) {
            'createOption' => $this->getCreateOptionSchema($configurator),
            'editOption', 'replicate' => $this->getEditOptionSchema($configurator),
            default => $this->getFormSchema($configurator),
        };
    }

    protected function getCreateOptionSchema(Schema $configurator): array
    {
        return [
            CreateDetailsSchema::make($configurator),
            Section::make()
                ->schema([$this->navigationSelect()]),
            TranslationsRepeater::make($configurator)
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

    protected function getEditOptionSchema(Schema $configurator): array
    {
        return [
            $this->navigationSelect(),
            TranslationsRepeater::make($configurator),
        ];
    }

    protected function getFormSchema(Schema $configurator): array
    {
        return [
            CreateDetailsSchema::make($configurator),
            FixedWidthSidebar::make()
                ->mainSchema([
                    TranslationsRepeater::make($configurator)
                        ->contained(),
                ])
                ->sidebarSchema(
                    SettingsSchema::make($configurator, [$this->navigationSelect()]),
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
