<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Elements;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\LayoutBuilder\Filament\Components\Forms\AssetsRepeater;
use Capell\LayoutBuilder\Filament\Components\Forms\ColorSchemeComponent;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\ComponentSection;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\CreateDetailsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\DisplaySection;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\ResultsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\SettingsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\Tab\ElementAdminTab;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\Tab\ElementDisplayTab;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\Tab\ElementSettingsTab;
use Capell\LayoutBuilder\Filament\Components\Forms\Element\TranslationsRepeater;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Override;

class AssetsElementConfigurator extends DefaultElementConfigurator
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

    #[Override]
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
                if ($get('elementAssets') === null) {
                    return null;
                }

                $count = count($get('elementAssets'));

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
        return ElementAdminTab::make();
    }

    #[Override]
    protected function settingsTab(Schema $configurator): Tab
    {
        return ElementSettingsTab::make($configurator);
    }

    #[Override]
    protected function displayTab(Schema $configurator): Tab
    {
        return ElementDisplayTab::make([
            Fieldset::make(__('capell-admin::generic.results'))
                ->columnSpanFull()
                ->statePath('meta')
                ->schema(ResultsSchema::make($configurator)),
            DisplaySection::make([
                ColorSchemeComponent::make('color'),
            ]),
            ComponentSection::make(),
        ]);
    }

    protected function getAssetsComponent(Schema $configurator): Component
    {
        return AssetsRepeater::make('elementAssets')
            ->compactRepeater()
            ->hiddenLabel()
            ->hint(__('capell-layout-builder::generic.element_assets_repeater_hint'));
    }
}
