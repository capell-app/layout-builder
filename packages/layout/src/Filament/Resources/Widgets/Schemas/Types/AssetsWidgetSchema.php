<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Schemas\Types;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\Media\MediaLibraryFileUpload;
use Capell\Layout\Filament\Components\Forms\AssetsRepeater;
use Capell\Layout\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Layout\Filament\Components\Forms\Widget\CreateWidgetDetailsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetDisplaySection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetTranslationsRepeater;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
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
                    $this->getContentTab($schema),
                    $this->getAssetsTab($schema),
                    $this->getSettingsTab($schema),
                    $this->getAdminTab($schema),
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
                            $this->getContentTab($schema),
                            $this->getSettingsTab($schema),
                            $this->getAdminTab($schema),
                        ]),
                ])
                ->sidebarSchema(
                    WidgetSettingsSchema::make($schema),
                    contained: true
                ),
        ];
    }

    protected function getAssetsTab(Schema $schema): Tab
    {
        return Tab::make(__('capell-admin::tab.assets'))
            ->schema([
                self::getAssetsComponent($schema),
            ]);
    }

    protected function getContentTab(Schema $schema): Tab
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
        return WidgetDisplayTab::make([
            Grid::make()
                ->statePath('meta')
                ->schema([
                    MediaLibraryFileUpload::make('image')
                        ->imageDefaults(),
                    WidgetDisplaySection::make([
                        ColorSchemeComponent::make('color_scheme'),
                    ]),
                    WidgetComponentFilesSection::make(),
                ]),
        ]);
    }

    protected function getAssetsComponent(Schema $schema): Component
    {
        return AssetsRepeater::make('assets')
            ->hiddenLabel();
    }
}
