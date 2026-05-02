<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Configurators\Widgets;

use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Concerns\HasConfigurator;
use Capell\Mosaic\Enums\ConfiguratorTypeEnum;
use Capell\Mosaic\Enums\SchemaExtenderEnum;
use Capell\Mosaic\Filament\Components\Forms\HeadingSizeSelect;
use Capell\Mosaic\Filament\Components\Forms\Widget\ComponentSection;
use Capell\Mosaic\Filament\Components\Forms\Widget\CreateDetailsSchema;
use Capell\Mosaic\Filament\Components\Forms\Widget\DisplaySection;
use Capell\Mosaic\Filament\Components\Forms\Widget\SettingsSchema;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PageContentWidgetConfigurator implements ConfiguratorInterface
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
            'createOption', 'editOption', 'replicate' => $this->getOptionSchema($configurator),
            default => $this->getFormSchema($configurator),
        };
    }

    protected function getTabs(): Tabs
    {
        return Tabs::make()
            ->columnSpanFull()
            ->tabs([
                WidgetDisplayTab::make([
                    Grid::make()
                        ->statePath('meta')
                        ->columnSpanFull()
                        ->schema([
                            CheckboxList::make('page_content')
                                ->label(__('capell-mosaic::form.page_content'))
                                ->helperText(__('capell-mosaic::generic.widget_page_content_helper'))
                                ->reactive()
                                ->columns(3)
                                ->options([
                                    'title' => __('capell-admin::generic.title'),
                                    'content' => __('capell-admin::generic.content'),
                                    'contents' => __('capell-admin::generic.contents'),
                                ]),
                            HeadingSizeSelect::make('heading_size')
                                ->visible(
                                    fn (Get $get): bool => $get('page_content') !== null && in_array('title', $get('page_content'), true),
                                ),
                        ]),
                    DisplaySection::make(),
                    ComponentSection::make()
                        ->statePath('meta'),
                ]),
                WidgetAdminTab::make(),
            ]);
    }

    protected function getFormSchema(Schema $configurator): array
    {
        return [
            CreateDetailsSchema::make($configurator),
            FixedWidthSidebar::make()
                ->mainSchema([
                    $this->getTabs(),
                ])
                ->sidebarSchema(
                    SettingsSchema::make($configurator),
                    contained: true,
                ),
        ];
    }

    protected function getOptionSchema(Schema $configurator): array
    {
        return [
            CreateDetailsSchema::make($configurator),
            $this->getTabs(),
        ];
    }
}
