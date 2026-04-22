<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Schemas\Widgets;

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

class AssetsWidgetSchema extends DefaultWidgetSchema
{
    #[Override]
    public function make(Schema $schema): array
    {
        return match ($schema->getOperation()) {
            'createOption', 'editOption', 'replicate' => $this->getOptionSchema($schema),
            default => $this->getFormSchema($schema),
        };
    }

    protected function getOptionSchema(Schema $schema): array
    {
        return [
            CreateDetailsSchema::make($schema),
            Tabs::make()
                ->columnSpanFull()
                ->tabs([
                    $this->assetsTab($schema),
                    $this->translationsTab($schema),
                    $this->displayTab($schema),
                    $this->adminTab($schema),
                    $this->settingsTab($schema),
                ]),
        ];
    }

    protected function getFormSchema(Schema $schema): array
    {
        return [
            CreateDetailsSchema::make($schema),
            FixedWidthSidebar::make()
                ->mainSchema([
                    Tabs::make()
                        ->columnSpanFull()
                        ->tabs([
                            $this->assetsTab($schema),
                            $this->translationsTab($schema),
                            $this->displayTab($schema),
                            $this->adminTab($schema),
                        ]),
                ])
                ->sidebarSchema([
                    Section::make()
                        ->gridContainer()
                        ->columns(['@md' => 2])
                        ->schema([
                            ...SettingsSchema::make($schema),
                            MediaLibraryFileUpload::make('image'),
                        ]),
                ]),
        ];
    }

    protected function assetsTab(Schema $schema): Tab
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
                self::getAssetsComponent($schema),
            ]);
    }

    protected function translationsTab(Schema $schema): Tab
    {
        return Tab::make(__('capell-admin::tab.content'))
            ->icon(Heroicon::Language)
            ->schema([
                TranslationsRepeater::make($schema)
                    ->contained(false),
            ]);
    }

    protected function adminTab(Schema $schema): Tab
    {
        return WidgetAdminTab::make();
    }

    protected function settingsTab(Schema $schema): Tab
    {
        return WidgetSettingsTab::make($schema);
    }

    protected function displayTab(Schema $schema): Tab
    {
        return WidgetDisplayTab::make([
            Fieldset::make(__('capell-admin::generic.results'))
                ->columnSpanFull()
                ->statePath('meta')
                ->schema(ResultsSchema::make($schema)),
            DisplaySection::make([
                ColorSchemeComponent::make('color'),
            ]),
            ComponentSection::make()
                ->statePath('meta'),
        ]);
    }

    protected function getAssetsComponent(Schema $schema): Component
    {
        return AssetsRepeater::make('widgetAssets')
            ->compactRepeater()
            ->hiddenLabel()
            ->hint(__('capell-mosaic::generic.widget_assets_repeater_hint'));
    }
}
