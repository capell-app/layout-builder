<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Configurators\Widgets;

use Capell\Admin\Filament\Components\Forms\CustomSelectGroup;
use Capell\Mosaic\Filament\Components\Forms\CarouselSettingsSchema;
use Capell\Mosaic\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Mosaic\Filament\Components\Forms\Widget\ComponentSection;
use Capell\Mosaic\Filament\Components\Forms\Widget\DisplaySection;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Override;

class HeroWidgetConfigurator extends AssetsWidgetConfigurator
{
    #[Override]
    protected function displayTab(Schema $configurator): Tab
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
                CustomSelectGroup::make(
                    name: 'height',
                    options: [
                        'full' => __('capell-admin::generic.full'),
                        'small' => __('capell-admin::generic.small'),
                        'medium' => __('capell-admin::generic.medium'),
                        'large' => __('capell-admin::generic.large'),
                    ],
                )
                    ->label(__('capell-admin::form.height')),
            ]),
            ComponentSection::make()
                ->statePath('meta'),
        ]);
    }
}
