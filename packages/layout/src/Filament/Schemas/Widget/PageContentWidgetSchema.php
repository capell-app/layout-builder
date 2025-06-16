<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Widget;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\HeadingSizeSelect;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetSettingsTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetDisplaySection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Filament\Forms;
use Filament\Forms\Get;

class PageContentWidgetSchema extends AbstractWidgetSchema
{
    public static function make(Forms\Form $form): array
    {
        return match ($form->getOperation()) {
            'create', 'editOption', 'createOption', 'replicate' => [
                self::getTabs(),
            ],
            default => [
                FixedWidthSidebar::make()
                    ->mainSchema([
                        self::getTabs(),
                    ])
                    ->sidebarSchema([
                        Forms\Components\Section::make()
                            ->schema(WidgetSettingsSchema::make($form)),
                    ]),
            ],
        };
    }

    private static function getTabs(): Forms\Components\Tabs
    {
        return Forms\Components\Tabs::make()
            ->columnSpanFull()
            ->tabs([
                WidgetSettingsTab::make([
                    Forms\Components\Group::make()
                        ->statePath('meta')
                        ->columns()
                        ->schema([
                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\CheckboxList::make('page_content')
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
