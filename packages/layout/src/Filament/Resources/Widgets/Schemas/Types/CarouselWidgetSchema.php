<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Schemas\Types;

use Capell\Layout\Filament\Components\Forms\CarouselSettingsSchema;
use Capell\Layout\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetDisplaySection;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Override;

class CarouselWidgetSchema extends AssetsWidgetSchema
{
    #[Override]
    protected static function getSettingsTab(Schema $schema): Tab
    {
        return WidgetDisplayTab::make([
            Grid::make()
                ->statePath('meta')
                ->schema([
                    Fieldset::make(
                        __('capell-admin::generic.carousel_options')
                    )
                        ->columns(['default' => 2, 'xl' => 3])
                        ->schema(CarouselSettingsSchema::make()),
                    WidgetDisplaySection::make([
                        ColorSchemeComponent::make('color_scheme'),
                    ]),
                    WidgetComponentFilesSection::make(),
                ]),
        ]);
    }
}
