<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Schemas\Types;

use Capell\Layout\Filament\Components\Forms\CarouselSettingsSchema;
use Capell\Layout\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Layout\Filament\Components\Forms\Widget\ComponentSection;
use Capell\Layout\Filament\Components\Forms\Widget\DisplaySection;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Override;

class CarouselWidgetSchema extends AssetsWidgetSchema
{
    #[Override]
    protected function displayTab(Schema $schema): Tab
    {
        return WidgetDisplayTab::make([
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
            ComponentSection::make()
                ->statePath('meta'),
        ]);
    }
}
