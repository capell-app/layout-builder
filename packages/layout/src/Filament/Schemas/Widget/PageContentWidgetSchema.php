<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Widget;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Layout\Filament\Components\Forms\HeadingSizeSelect;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetDisplaySection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Capell\Layout\Filament\Schemas\AbstractWidgetSchema;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PageContentWidgetSchema extends AbstractWidgetSchema
{
    public static function make(Schema $schema): array
    {
        return match ($schema->getOperation()) {
            'create', 'editOption', 'createOption', 'replicate' => [
                self::getTabs(),
            ],
            default => [
                FixedWidthSidebar::make()
                    ->mainSchema([
                        self::getTabs(),
                    ])
                    ->sidebarSchema([
                        Section::make()
                            ->schema(WidgetSettingsSchema::make($schema)),
                    ]),
            ],
        };
    }

    protected static function getTabs(): Tabs
    {
        return Tabs::make()
            ->columnSpanFull()
            ->tabs([
                WidgetDisplayTab::make([
                    Group::make()
                        ->statePath('meta')
                        ->columns()
                        ->schema([
                            Grid::make()
                                ->schema([
                                    CheckboxList::make('page_content')
                                        ->label(__('capell-admin::form.page_content'))
                                        ->helperText(__('capell-admin::generic.widget_page_content_helper'))
                                        ->reactive()
                                        ->columns(3)
                                        ->options([
                                            'title' => __('capell-admin::generic.title'),
                                            'content' => __('capell-admin::generic.content'),
                                            'contents' => __('capell-admin::generic.contents'),
                                        ]),
                                    HeadingSizeSelect::make('heading_size')
                                        ->visible(
                                            fn (Get $get): bool => in_array(
                                                'title',
                                                $get('page_content') ?: [],
                                                true
                                            )
                                        ),
                                ]),
                            WidgetDisplaySection::make(),
                            WidgetComponentFilesSection::make(),
                        ]),
                ]),
                WidgetAdminTab::make(),
            ]);
    }
}
