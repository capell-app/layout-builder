<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Blocks;

use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Concerns\HasConfigurator;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Enums\SchemaExtenderEnum;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\ComponentSection;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\CreateDetailsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\DisplaySection;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\SettingsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\Tab\BlockAdminTab;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\Tab\BlockDisplayTab;
use Capell\LayoutBuilder\Filament\Components\Forms\HeadingSizeSelect;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PageContentBlockConfigurator implements ConfiguratorInterface
{
    use HasConfigurator;

    protected static ConfiguratorTypeEnumInterface $configuratorType = ConfiguratorTypeEnum::Block;

    /**
     * @return iterable<int, mixed>
     */
    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::Block->value);
    }

    /**
     * @return array<array-key, mixed>
     */
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
                BlockDisplayTab::make([
                    Grid::make()
                        ->statePath('meta')
                        ->columnSpanFull()
                        ->schema([
                            CheckboxList::make('page_content')
                                ->label(__('capell-layout-builder::form.page_content'))
                                ->helperText(__('capell-layout-builder::generic.block_page_content_helper'))
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
                    ComponentSection::make(),
                ]),
                BlockAdminTab::make(),
            ]);
    }

    /**
     * @return array<array-key, mixed>
     */
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

    /**
     * @return array<array-key, mixed>
     */
    protected function getOptionSchema(Schema $configurator): array
    {
        return [
            CreateDetailsSchema::make($configurator),
            $this->getTabs(),
        ];
    }
}
