<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Configurators\Widgets;

use Capell\Mosaic\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Mosaic\Filament\Components\Forms\Widget\ComponentSection;
use Capell\Mosaic\Filament\Components\Forms\Widget\DisplaySection;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Override;

class ImageGalleryWidgetConfigurator extends DefaultWidgetConfigurator
{
    #[Override]
    protected function displayTab(Schema $configurator): Tab
    {
        return WidgetDisplayTab::make([
            DisplaySection::make([
                ColorSchemeComponent::make('color'),
            ]),
            ComponentSection::make()
                ->statePath('meta'),
        ]);
    }

    #[Override]
    protected function detailsTab(): Tab
    {
        return Tab::make('gallery_details')
            ->label(__('capell-admin::tab.details'))
            ->icon('heroicon-o-information-circle')
            ->statePath('meta')
            ->schema([
                Fieldset::make(__('capell-mosaic::form.gallery_settings'))
                    ->columns(['default' => 1, 'lg' => 2])
                    ->schema([
                        Select::make('layout')
                            ->label(__('capell-mosaic::form.layout'))
                            ->options(['grid' => 'Grid', 'carousel' => 'Carousel'])
                            ->default('grid'),
                        Select::make('columns')
                            ->label(__('capell-mosaic::form.columns'))
                            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4'])
                            ->default(3)
                            ->visible(fn (callable $get): bool => $get('layout') === 'grid'),
                        Toggle::make('lightbox')
                            ->label(__('capell-mosaic::form.lightbox'))
                            ->default(true),
                    ]),
            ]);
    }
}
