<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Blocks;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\ImageSourcePicker;
use Capell\LayoutBuilder\Filament\Components\Forms\AssetsRepeater;
use Capell\LayoutBuilder\Filament\Components\Forms\ColorSchemeComponent;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\ComponentSection;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\CreateDetailsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\DisplaySection;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\ResultsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\SettingsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab\BlockAdminTab;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab\BlockDisplayTab;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab\BlockSettingsTab;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\TranslationsRepeater;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Override;

class AssetsBlockConfigurator extends DefaultBlockConfigurator
{
    #[Override]
    public function make(Schema $configurator): array
    {
        return match ($configurator->getOperation()) {
            'createOption', 'editOption', 'replicate' => $this->getOptionSchema($configurator),
            default => $this->getFormSchema($configurator),
        };
    }

    /**
     * @return array<array-key, mixed>
     */
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
                            ImageSourcePicker::make('image')
                                ->sourceStatePath('meta.image_source')
                                ->imageSourcePolicy(blueprintSources: $this->blueprintImageSourcePolicy($configurator, 'image')),
                        ]),
                ]),
        ];
    }

    protected function assetsTab(Schema $configurator): Tab
    {
        return Tab::make(__('capell-admin::tab.assets'))
            ->badge(function (Get $get): ?int {
                if ($get('blockAssets') === null) {
                    return null;
                }

                $count = count($get('blockAssets'));

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
        return BlockAdminTab::make();
    }

    #[Override]
    protected function settingsTab(Schema $configurator): Tab
    {
        return BlockSettingsTab::make($configurator);
    }

    #[Override]
    protected function displayTab(Schema $configurator): Tab
    {
        return BlockDisplayTab::make([
            Fieldset::make(__('capell-admin::generic.results'))
                ->columnSpanFull()
                ->statePath('meta')
                ->schema(ResultsSchema::make($configurator)),
            DisplaySection::make([
                ...$this->extendDisplayComponents($configurator, [
                    ColorSchemeComponent::make('color'),
                ]),
            ]),
            ComponentSection::make(),
        ]);
    }

    protected function getAssetsComponent(Schema $configurator): Component
    {
        return AssetsRepeater::make('blockAssets')
            ->compactRepeater()
            ->hiddenLabel()
            ->hint(__('capell-layout-builder::generic.block_assets_repeater_hint'));
    }
}
