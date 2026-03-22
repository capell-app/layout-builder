<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Schemas\Types;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\Layout\Filament\Components\Forms\AssetsRepeater;
use Capell\Layout\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Layout\Filament\Components\Forms\Widget\ComponentSection;
use Capell\Layout\Filament\Components\Forms\Widget\CreateDetailsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\DisplaySection;
use Capell\Layout\Filament\Components\Forms\Widget\ResultsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\SettingsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetSettingsTab;
use Capell\Layout\Filament\Components\Forms\Widget\TranslationsRepeater;
use Filament\Schemas\Components\Component;
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
                    $this->getAssetsTab($schema),
                    $this->getTranslationsTab($schema),
                    $this->getDisplayTab($schema),
                    $this->adminTab($schema),
                    $this->getSettingsTab($schema),
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
                            $this->getAssetsTab($schema),
                            $this->getTranslationsTab($schema),
                            $this->getDisplayTab($schema),
                            $this->adminTab($schema),
                        ]),
                ])
                ->sidebarSchema(
                    SettingsSchema::make($schema),
                    contained: true,
                ),
        ];
    }

    protected function getAssetsTab(Schema $schema): Tab
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

    protected function getTranslationsTab(Schema $schema): Tab
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

    protected function getSettingsTab(Schema $schema): Tab
    {
        return WidgetSettingsTab::make($schema);
    }

    protected function getDisplayTab(Schema $schema): Tab
    {
        return WidgetDisplayTab::make([
            MediaLibraryFileUpload::make('image'),
            DisplaySection::make([
                ColorSchemeComponent::make('color'),
                ...ResultsSchema::make($schema),
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
            ->hint(__('capell-layout::generic.widget_assets_repeater_hint'));
    }
}
