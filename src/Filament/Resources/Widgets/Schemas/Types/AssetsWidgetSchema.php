<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Schemas\Types;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\Layout\Filament\Components\Forms\AssetsRepeater;
use Capell\Layout\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Layout\Filament\Components\Forms\Widget\CreateWidgetDetailsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetSettingsTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetDisplaySection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetResultsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetTranslationsRepeater;
use Filament\Schemas\Components\Component;
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
            CreateWidgetDetailsSchema::make($schema),
            Tabs::make()
                ->columnSpanFull()
                ->tabs([
                    $this->getAssetsTab($schema),
                    $this->getTranslationsTab($schema),
                    $this->getDisplayTab($schema),
                    $this->getAdminTab($schema),
                    $this->getSettingsTab($schema),
                ]),
        ];
    }

    protected function getFormSchema(Schema $schema): array
    {
        return [
            CreateWidgetDetailsSchema::make($schema),
            FixedWidthSidebar::make()
                ->mainSchema([
                    Tabs::make()
                        ->columnSpanFull()
                        ->tabs([
                            $this->getAssetsTab($schema),
                            $this->getTranslationsTab($schema),
                            $this->getDisplayTab($schema),
                            $this->getAdminTab($schema),
                        ]),
                ])
                ->sidebarSchema(
                    WidgetSettingsSchema::make($schema),
                    contained: true,
                ),
        ];
    }

    protected function getAssetsTab(Schema $schema): Tab
    {
        return Tab::make(__('capell-admin::tab.assets'))
            ->badge(function (Get $get): ?int {
                if (! $get('widgetAssets')) {
                    return null;
                }

                $count = count($get('widgetAssets'));

                return $count > 0 ? $count : null;
            })
            ->schema([
                self::getAssetsComponent($schema),
            ]);
    }

    protected function getTranslationsTab(Schema $schema): Tab
    {
        return Tab::make(__('capell-admin::tab.content'))
            ->icon(Heroicon::Language)
            ->schema([
                WidgetTranslationsRepeater::make($schema)
                    ->contained(false),
            ]);
    }

    protected function getAdminTab(Schema $schema): Tab
    {
        return WidgetAdminTab::make();
    }

    protected function getSettingsTab(Schema $schema): Tab
    {
        return WidgetSettingsTab::make($schema);
    }

    protected function getDisplayTab(Schema $schema): Tab
    {
        return WidgetDisplayTab::make([
            MediaLibraryFileUpload::make('image'),
            Section::make(__('capell-admin::generic.results_settings'))
                ->collapsible()
                ->columnSpanFull()
                ->columns(['md' => 2, 'lg' => 3, 'xl' => 4])
                ->statePath('meta')
                ->schema(WidgetResultsSchema::make()),
            WidgetDisplaySection::make([
                ColorSchemeComponent::make('color_scheme'),
            ]),
            WidgetComponentFilesSection::make()
                ->statePath('meta'),
        ]);
    }

    protected function getAssetsComponent(Schema $schema): Component
    {
        return AssetsRepeater::make('widgetAssets')
            ->compact()
            ->hiddenLabel()
            ->hint(__('capell-admin::generic.widget_assets_repeater_hint'));
    }
}
