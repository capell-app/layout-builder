<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Configurators\Widgets;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\Mosaic\Filament\Components\Forms\AssetsRepeater;
use Capell\Mosaic\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Mosaic\Filament\Components\Forms\Widget\ComponentSection;
use Capell\Mosaic\Filament\Components\Forms\Widget\CreateDetailsSchema;
use Capell\Mosaic\Filament\Components\Forms\Widget\DisplaySection;
use Capell\Mosaic\Filament\Components\Forms\Widget\ResultsSchema;
use Capell\Mosaic\Filament\Components\Forms\Widget\SettingsSchema;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetSettingsTab;
use Capell\Mosaic\Filament\Components\Forms\Widget\TranslationsRepeater;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Override;

class AssetsWidgetConfigurator extends DefaultWidgetConfigurator
{
    #[Override]
    public function make(Schema $configurator): array
    {
        return match ($configurator->getOperation()) {
            'createOption', 'editOption', 'replicate' => $this->getOptionSchema($configurator),
            default => $this->getFormSchema($configurator),
        };
    }

    protected function getOptionSchema(Schema $configurator): array
    {
        return [
            CreateDetailsSchema::make($configurator),
            Tabs::make()
                ->columnSpanFull()
                ->tabs([
                    $this->assetsTab($configurator),
                    $this->translationsTab($configurator),
                    $this->displayTab($configurator),
                    $this->adminTab($configurator),
                    $this->settingsTab($configurator),
                ]),
        ];
    }

    protected function getFormSchema(Schema $configurator): array
    {
        return [
            CreateDetailsSchema::make($configurator),
            FixedWidthSidebar::make()
                ->mainSchema([
                    Tabs::make()
                        ->columnSpanFull()
                        ->tabs([
                            $this->assetsTab($configurator),
                            $this->translationsTab($configurator),
                            $this->displayTab($configurator),
                            $this->adminTab($configurator),
                        ]),
                ])
                ->sidebarSchema([
                    Section::make()
                        ->gridContainer()
                        ->columns(['@md' => 2])
                        ->schema([
                            ...SettingsSchema::make($configurator),
                            MediaLibraryFileUpload::make('image'),
                        ]),
                ]),
        ];
    }

    protected function assetsTab(Schema $configurator): Tab
    {
        return Tab::make(__('capell-admin::tab.assets'))
            ->badge(function (Get $get): ?int {
                if ($get('widgetAssets') === null) {
                    return null;
                }

                $count = count($get('widgetAssets'));

                return $count > 0 ? $count : null;
            })
            ->schema([
                self::getAssetsComponent($configurator),
            ]);
    }

    protected function translationsTab(Schema $configurator): Tab
    {
        return Tab::make(__('capell-admin::tab.content'))
            ->icon(Heroicon::Language)
            ->schema([
                TranslationsRepeater::make($configurator)
                    ->contained(false),
            ]);
    }

    protected function adminTab(Schema $configurator): Tab
    {
        return WidgetAdminTab::make();
    }

    protected function settingsTab(Schema $configurator): Tab
    {
        return WidgetSettingsTab::make($configurator);
    }

    protected function displayTab(Schema $configurator): Tab
    {
        return WidgetDisplayTab::make([
            Fieldset::make(__('capell-admin::generic.results'))
                ->columnSpanFull()
                ->statePath('meta')
                ->schema(ResultsSchema::make($configurator)),
            DisplaySection::make([
                ColorSchemeComponent::make('color'),
            ]),
            ComponentSection::make()
                ->statePath('meta'),
        ]);
    }

    protected function getAssetsComponent(Schema $configurator): Component
    {
        return AssetsRepeater::make('widgetAssets')
            ->compactRepeater()
            ->hiddenLabel()
            ->hint(__('capell-mosaic::generic.widget_assets_repeater_hint'));
    }
}
