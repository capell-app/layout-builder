<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types;

use Capell\Mosaic\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Mosaic\Filament\Components\Forms\Widget\ComponentSection;
use Capell\Mosaic\Filament\Components\Forms\Widget\DisplaySection;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Repeater;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\Toggle;
use Filament\Schemas\Schema;
use Override;

class ImageGalleryWidgetSchema extends DefaultWidgetSchema
{
    #[Override]
    protected function displayTab(Schema $schema): Tab
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
                Fieldset::make(__('capell-mosaic::form.images'))
                    ->columns(['default' => 1])
                    ->schema([
                        Repeater::make('images')
                            ->label('')
                            ->addActionLabel(__('capell-mosaic::form.add_image'))
                            ->columns(['default' => 1, 'lg' => 2])
                            ->schema([
                                TextInput::make('url')
                                    ->label(__('capell-mosaic::form.image_url'))
                                    ->placeholder('image-url.jpg')
                                    ->url()
                                    ->required(),
                                TextInput::make('alt')
                                    ->label(__('capell-mosaic::form.alt_text'))
                                    ->placeholder('Alt text')
                                    ->required(),
                            ]),
                    ]),
            ]);
    }
}
