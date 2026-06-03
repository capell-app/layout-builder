<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Widgets;

use Capell\LayoutBuilder\Filament\Components\Forms\ColorSchemeComponent;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab\WidgetPresentationTabs;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Override;

class FeatureListWidgetConfigurator extends DefaultWidgetConfigurator
{
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
        );
    }

    #[Override]
    protected function detailsTab(): Tab
    {
        return Tab::make('feature_details')
            ->label(__('capell-admin::tab.details'))
            ->icon('heroicon-o-information-circle')
            ->statePath('meta')
            ->schema([
                Fieldset::make(__('capell-layout-builder::form.layout_settings'))
                    ->columns(['default' => 1])
                    ->schema([
                        Select::make('layout')
                            ->label(__('capell-layout-builder::form.layout'))
                            ->options(['vertical' => 'Vertical', 'horizontal' => 'Horizontal'])
                            ->default('vertical'),
                    ]),
            ]);
    }
}
