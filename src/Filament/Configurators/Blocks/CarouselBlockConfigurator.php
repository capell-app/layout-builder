<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Blocks;

use Capell\LayoutBuilder\Filament\Components\Forms\Block\ComponentSection;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\DisplaySection;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\Tab\BlockDisplayTab;
use Capell\LayoutBuilder\Filament\Components\Forms\CarouselSettingsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\ColorSchemeComponent;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Override;

class CarouselBlockConfigurator extends AssetsBlockConfigurator
{
    #[Override]
    protected function displayTab(Schema $configurator): Tab
    {
        return BlockDisplayTab::make([
            Fieldset::make(
                __('capell-admin::generic.carousel_options'),
            )
                ->statePath('meta')
                ->columnSpanFull()
                ->columns(['default' => 2, 'xl' => 3])
                ->schema(CarouselSettingsSchema::make()),
            DisplaySection::make([
                ColorSchemeComponent::make('color'),
            ]),
            ComponentSection::make(),
        ]);
    }
}
