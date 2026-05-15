<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Elements;

use Capell\Admin\Filament\Components\Forms\CustomSelectGroup;
use Capell\LayoutBuilder\Filament\Components\Forms\CarouselSettingsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\ColorSchemeComponent;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\ComponentSection;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\DisplaySection;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\Tab\ElementDisplayTab;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Override;

class HeroElementConfigurator extends AssetsElementConfigurator
{
    #[Override]
    protected function displayTab(Schema $configurator): Tab
    {
        return ElementDisplayTab::make([
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
                Select::make('content_align')
                    ->label(__('capell-admin::form.alignment'))
                    ->default('center')
                    ->options([
                        'center' => __('capell-admin::generic.center'),
                        'left' => __('capell-admin::generic.left'),
                    ]),
                Select::make('content_width')
                    ->label(__('capell-admin::form.max_width'))
                    ->default('balanced')
                    ->options([
                        'compact' => __('capell-admin::generic.sm'),
                        'balanced' => __('capell-admin::generic.default'),
                        'wide' => __('capell-admin::generic.lg'),
                    ]),
                Select::make('media_position')
                    ->label(__('capell-admin::form.image'))
                    ->default('right')
                    ->options([
                        'right' => __('capell-admin::generic.right'),
                        'left' => __('capell-admin::generic.left'),
                    ]),
            ]),
            ComponentSection::make(),
        ]);
    }
}
