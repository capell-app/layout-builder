<?php

declare(strict_types=1);

namespace Capell\Hero\Filament\Resources\Widgets\Schemas\Types;

use Capell\Layout\Filament\Components\Forms\CarouselSettingsSchema;
use Capell\Layout\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetDisplaySection;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\AssetsWidgetSchema;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Override;

class HeroWidgetSchema extends AssetsWidgetSchema
{
    #[Override]
    protected function getDisplayTab(Schema $schema): Tab
    {
        return WidgetDisplayTab::make([
            Fieldset::make(
                __('capell-admin::generic.carousel_options')
            )
                ->statePath('meta')
                ->columnSpanFull()
                ->columns(['default' => 2, 'xl' => 3])
                ->schema(CarouselSettingsSchema::make()),
            WidgetDisplaySection::make([
                ColorSchemeComponent::make('color_scheme'),
                Select::make('height')
                    ->label(__('capell-admin::form.height'))
                    ->placeholder(__('capell-admin::generic.none'))
                    ->options([
                        'full' => __('capell-admin::generic.full'),
                        'small' => __('capell-admin::generic.small'),
                        'medium' => __('capell-admin::generic.medium'),
                        'large' => __('capell-admin::generic.large'),
                    ])
                    ->default('medium')
                    ->required(),
            ]),
            WidgetComponentFilesSection::make()
                ->statePath('meta'),
        ]);
    }
}
