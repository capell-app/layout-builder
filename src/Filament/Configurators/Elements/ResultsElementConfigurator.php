<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Elements;

use Capell\Admin\Filament\Components\Forms\CacheFrequencySelect;
use Capell\Admin\Filament\Components\Forms\ContentEditor;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\ComponentSection;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\CreateDetailsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\DisplaySection;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\ResultsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\SettingsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\Tab\ElementAdminTab;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\Tab\ElementDisplayTab;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\TranslationsRepeater;
use Capell\LayoutBuilder\Filament\Components\Forms\PageModelSelect;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Override;

class ResultsElementConfigurator extends DefaultElementConfigurator
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

    #[Override]
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

    #[Override]
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
                            ->label(__('capell-layout-builder::form.limit')),
                        CacheFrequencySelect::make('cache_frequency'),
                        Grid::make()
                            ->columnSpanFull()
                            ->schema([
                                Checkbox::make('pagination')
                                    ->label(__('capell-layout-builder::form.pagination'))
                                    ->default(true),
                                ...ResultsSchema::make($configurator),
                            ]),
                    ]),
                ElementDisplayTab::make([
                    DisplaySection::make(),
                    ComponentSection::make(),
                ]),
                ElementAdminTab::make(),
            ]);
    }
}
