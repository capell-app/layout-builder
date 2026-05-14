<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Widgets;

use Capell\LayoutBuilder\Filament\Components\Forms\ColorSchemeComponent;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\ComponentSection;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\DisplaySection;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Override;

class CardGridWidgetConfigurator extends DefaultWidgetConfigurator
{
    #[Override]
    protected function displayTab(Schema $configurator): Tab
    {
        return WidgetDisplayTab::make([
            DisplaySection::make([
                ColorSchemeComponent::make('color'),
            ]),
            ComponentSection::make()
                ->statePath('meta'),
        ]);
    }

    #[Override]
    protected function detailsTab(): Tab
    {
        return Tab::make('card_details')
            ->label(__('capell-admin::tab.details'))
            ->icon('heroicon-o-information-circle')
            ->statePath('meta')
            ->schema([
                Fieldset::make(__('capell-layout-builder::form.grid_settings'))
                    ->columns(['default' => 1])
                    ->schema([
                        Select::make('columns')
                            ->label(__('capell-layout-builder::form.columns'))
                            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4'])
                            ->default(3),
                    ]),
            ]);
    }
}
