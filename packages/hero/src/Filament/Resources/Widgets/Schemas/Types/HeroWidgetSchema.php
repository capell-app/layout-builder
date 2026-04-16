<?php

declare(strict_types=1);

namespace Capell\Hero\Filament\Resources\Widgets\Schemas\Types;

use Capell\Admin\Filament\Components\Forms\CustomSelectGroup;
use Capell\Layout\Filament\Components\Forms\CarouselSettingsSchema;
use Capell\Layout\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Layout\Filament\Components\Forms\Widget\ComponentSection;
use Capell\Layout\Filament\Components\Forms\Widget\DisplaySection;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\AssetsWidgetSchema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Override;

class HeroWidgetSchema extends AssetsWidgetSchema
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
                CustomSelectGroup::make(
                    name: 'height',
                    options: [
                        'full' => __('capell-admin::generic.full'),
                        'small' => __('capell-admin::generic.small'),
                        'medium' => __('capell-admin::generic.medium'),
                        'large' => __('capell-admin::generic.large'),
                    ],
                    placeholder: __('capell-admin::generic.auto'),
                )
                    ->label(__('capell-admin::form.height')),
            ]),
            ComponentSection::make()
                ->statePath('meta'),
        ]);
    }
}
