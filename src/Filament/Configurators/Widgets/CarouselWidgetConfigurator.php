<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Widgets;

use Capell\LayoutBuilder\Filament\Components\Forms\CarouselSettingsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\ColorSchemeComponent;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab\WidgetPresentationTabs;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Override;

class CarouselWidgetConfigurator extends AssetsWidgetConfigurator
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
            itemsSchema: [
                Fieldset::make(__('capell-admin::generic.carousel_options'))
                    ->statePath('meta')
                    ->columnSpanFull()
                    ->columns(['default' => 2, 'xl' => 3])
                    ->schema(CarouselSettingsSchema::make()),
            ],
        );
    }
}
