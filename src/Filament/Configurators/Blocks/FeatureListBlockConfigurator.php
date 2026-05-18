<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Blocks;

use Capell\LayoutBuilder\Filament\Components\Forms\Block\ComponentSection;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\DisplaySection;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\Tab\BlockDisplayTab;
use Capell\LayoutBuilder\Filament\Components\Forms\ColorSchemeComponent;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Override;

class FeatureListBlockConfigurator extends DefaultBlockConfigurator
{
    #[Override]
    protected function displayTab(Schema $configurator): Tab
    {
        return BlockDisplayTab::make([
            DisplaySection::make([
                ColorSchemeComponent::make('color'),
            ]),
            ComponentSection::make(),
        ]);
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
