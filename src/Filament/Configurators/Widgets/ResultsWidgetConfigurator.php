<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Configurators\Widgets;

use Capell\Admin\Filament\Components\Forms\CacheFrequencySelect;
use Capell\Admin\Filament\Components\Forms\ContentEditor;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Mosaic\Filament\Components\Forms\PageModelSelect;
use Capell\Mosaic\Filament\Components\Forms\Widget\ComponentSection;
use Capell\Mosaic\Filament\Components\Forms\Widget\CreateDetailsSchema;
use Capell\Mosaic\Filament\Components\Forms\Widget\DisplaySection;
use Capell\Mosaic\Filament\Components\Forms\Widget\ResultsSchema;
use Capell\Mosaic\Filament\Components\Forms\Widget\SettingsSchema;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Mosaic\Filament\Components\Forms\Widget\TranslationsRepeater;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Override;

class ResultsWidgetConfigurator extends DefaultWidgetConfigurator
{
    #[Override]
    public function make(Schema $configurator): array
    {
        $operation = $configurator->getOperation();

        return match ($operation) {
            'createOption', 'replicate', 'editOption' => $this->getOptionSchema($configurator),
            default => $this->getFormSchema($configurator),
        };
    }

    protected function getOptionSchema(Schema $configurator): array
    {
        return [
            CreateDetailsSchema::make($configurator),
            TranslationsRepeater::make($configurator, components: [
                Group::make()
                    ->statePath('meta')
                    ->schema([
                        ContentEditor::make('no_results')
                            ->label(__('capell-admin::form.no_results'))
                            ->hint(__('capell-admin::generic.no_results_info')),
                    ]),
            ])
                ->contained(fn (string $operation): bool => $operation === 'create'),
            $this->getTabs($configurator),
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
                    SettingsSchema::make($configurator),
                    contained: true,
                ),
            $this->getTabs($configurator),
        ];
    }

    protected function getTabs(Schema $configurator, bool $withSettingsTab = false): Tabs
    {
        return Tabs::make()
            ->columnSpanFull()
            ->tabs([
                Tab::make(__('capell-admin::generic.results'))
                    ->statePath('meta')
                    ->columns()
                    ->schema([
                        PageModelSelect::make('page_model'),
                        TextInput::make('limit')
                            ->label(__('capell-mosaic::form.limit')),
                        CacheFrequencySelect::make('cache_frequency'),
                        Grid::make()
                            ->columnSpanFull()
                            ->schema([
                                Checkbox::make('pagination')
                                    ->label(__('capell-mosaic::form.pagination'))
                                    ->default(true),
                                ...ResultsSchema::make($configurator),
                            ]),
                    ]),
                WidgetDisplayTab::make([
                    DisplaySection::make(),
                    ComponentSection::make()
                        ->statePath('meta'),
                ]),
                WidgetAdminTab::make(),
            ]);
    }
}
