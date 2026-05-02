<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Configurators\Widgets;

use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\Admin\Filament\Concerns\HasConfigurator;
use Capell\Mosaic\Enums\ConfiguratorTypeEnum;
use Capell\Mosaic\Enums\SchemaExtenderEnum;
use Capell\Mosaic\Filament\Components\Forms\ActionsRepeater;
use Capell\Mosaic\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Mosaic\Filament\Components\Forms\Widget\ComponentSection;
use Capell\Mosaic\Filament\Components\Forms\Widget\CreateDetailsSchema;
use Capell\Mosaic\Filament\Components\Forms\Widget\DisplaySection;
use Capell\Mosaic\Filament\Components\Forms\Widget\SettingsSchema;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetSettingsTab;
use Capell\Mosaic\Filament\Components\Forms\Widget\TranslationsRepeater;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class DefaultWidgetConfigurator implements ConfiguratorInterface
{
    use HasConfigurator;

    protected static ConfiguratorTypeEnumInterface $configuratorType = ConfiguratorTypeEnum::Widget;

    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::Widget->value);
    }

    public function make(Schema $configurator): array
    {
        return match ($configurator->getOperation()) {
            'createOption', 'replicate' => $this->getCreateOptionSchema($configurator),
            'editOption' => $this->getEditOptionSchema($configurator),
            default => $this->getFormSchema($configurator),
        };
    }

    protected function getFormSchema(Schema $configurator): array
    {
        return [
            CreateDetailsSchema::make($configurator),
            FixedWidthSidebar::make()
                ->mainSchema([
                    TranslationsRepeater::make($configurator)
                        ->contained(),
                    ...$this->getExtraSchema($configurator),
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

    protected function getEditOptionSchema(Schema $configurator): array
    {
        return [
            TranslationsRepeater::make($configurator),
            ...$this->getExtraSchema($configurator, withSettingsTab: true),
        ];
    }

    protected function getCreateOptionSchema(Schema $configurator): array
    {
        return [
            CreateDetailsSchema::make($configurator),
            TranslationsRepeater::make($configurator),
            ...$this->getExtraSchema($configurator),
        ];
    }

    protected function getExtraSchema(Schema $configurator, bool $withSettingsTab = false): array
    {
        return [
            $this->getTabs($configurator, $withSettingsTab),
        ];
    }

    protected function getTabs(Schema $configurator, bool $withSettingsTab = false): Tabs
    {
        return Tabs::make()
            ->columnSpanFull()
            ->tabs([
                $this->detailsTab(),
                $this->displayTab($configurator),
                ...$withSettingsTab ? [$this->settingsTab($configurator)] : [],
                WidgetAdminTab::make(),
            ]);
    }

    protected function displayTab(Schema $configurator): Tab
    {
        return WidgetDisplayTab::make([
            DisplaySection::make([
                ColorSchemeComponent::make('color'),
                Checkbox::make('reverse_order')
                    ->label(__('capell-mosaic::form.reverse_order'))
                    ->whenTruthy('image'),
            ]),
            ComponentSection::make()
                ->statePath('meta'),
        ]);
    }

    protected function detailsTab(): Tab
    {
        return Tab::make('details')
            ->label(__('capell-admin::tab.details'))
            ->icon('heroicon-o-information-circle')
            ->statePath('meta')
            ->schema([
                ActionsRepeater::make('actions'),
            ]);
    }

    protected function settingsTab(Schema $configurator): Tab
    {
        return WidgetSettingsTab::make($configurator);
    }
}
