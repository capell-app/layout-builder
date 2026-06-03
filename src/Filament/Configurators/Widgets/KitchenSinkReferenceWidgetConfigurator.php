<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Widgets;

use Capell\LayoutBuilder\Filament\Components\Forms\ColorSchemeComponent;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab\WidgetPresentationTabs;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Override;

class KitchenSinkReferenceWidgetConfigurator extends DefaultWidgetConfigurator
{
    #[Override]
    protected function detailsTab(): Tab
    {
        return Tab::make('reference_details')
            ->label(__('capell-admin::tab.details'))
            ->icon('heroicon-o-information-circle')
            ->statePath('meta')
            ->schema([
                Fieldset::make(__('capell-layout-builder::form.reference_sections'))
                    ->columns(['default' => 1])
                    ->schema([
                        TextInput::make('family')
                            ->label(__('capell-layout-builder::form.reference_family'))
                            ->required()
                            ->maxLength(80),
                        Repeater::make('sections')
                            ->label(__('capell-layout-builder::form.reference_sections'))
                            ->schema([
                                TextInput::make('key')
                                    ->label(__('capell-layout-builder::form.reference_section_key'))
                                    ->required()
                                    ->alphaDash()
                                    ->maxLength(80),
                                TextInput::make('heading')
                                    ->label(__('capell-layout-builder::form.heading'))
                                    ->required()
                                    ->maxLength(120),
                                Textarea::make('summary')
                                    ->label(__('capell-layout-builder::form.summary'))
                                    ->rows(2)
                                    ->maxLength(240)
                                    ->columnSpanFull(),
                            ])
                            ->columns(['default' => 1, 'lg' => 2])
                            ->addActionLabel(__('capell-layout-builder::form.add_reference_section'))
                            ->minItems(1)
                            ->reorderable()
                            ->collapsible(),
                    ]),
            ]);
    }

    /**
     * @return array<int, Tab>
     */
    #[Override]
    protected function displayTabs(Schema $configurator): array
    {
        return WidgetPresentationTabs::make(
            styleFields: [
                ColorSchemeComponent::make('color'),
            ],
            withComponentSection: false,
        );
    }
}
