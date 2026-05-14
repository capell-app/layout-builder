<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Widgets;

use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\LayoutBuilder\Filament\Components\Forms\ColorSchemeComponent;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\ComponentSection;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\DisplaySection;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Override;

class HeroBannerWidgetConfigurator extends DefaultWidgetConfigurator
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
        return Tab::make('hero_details')
            ->label(__('capell-admin::tab.details'))
            ->icon('heroicon-o-information-circle')
            ->statePath('meta')
            ->schema([
                Fieldset::make(__('capell-layout-builder::form.hero_settings'))
                    ->columns(['default' => 1, 'lg' => 2])
                    ->schema([
                        TextInput::make('primary_button_text')
                            ->label(__('capell-layout-builder::form.primary_button_text'))
                            ->placeholder('Get Started'),
                        TextInput::make('primary_button_url')
                            ->label(__('capell-layout-builder::form.primary_button_url'))
                            ->placeholder('/signup')
                            ->url(),
                        TextInput::make('secondary_button_text')
                            ->label(__('capell-layout-builder::form.secondary_button_text'))
                            ->placeholder('Learn More'),
                        TextInput::make('secondary_button_url')
                            ->label(__('capell-layout-builder::form.secondary_button_url'))
                            ->placeholder('/docs')
                            ->url(),
                        MediaLibraryFileUpload::make('background_image')
                            ->label(__('capell-layout-builder::form.background_image'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
