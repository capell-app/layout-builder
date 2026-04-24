<?php

declare(strict_types=1);

namespace Capell\Navigation\Filament\Components\Forms\Page\Tab;

use Capell\Navigation\Filament\Components\Forms\NavigationSelect;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs\Tab;

class NavigationTab
{
    public static function make(): Tab
    {
        return Tab::make(__('capell-admin::tab.navigation'))
            ->icon('heroicon-o-globe-alt')
            ->schema([
                Grid::make()
                    ->schema([
                        NavigationSelect::make('page_navigations')
                            ->multiple()
                            ->dehydrated(false)
                            ->helperText(__('capell-admin::generic.page_navigations_info')),

                        ViewField::make('navigations')
                            ->dehydrated(false)
                            ->visibleOn('edit')
                            ->view('capell-navigation::components.page.navigations'),
                    ]),

                ViewField::make('page_tree')
                    ->dehydrated(false)
                    ->view('capell-admin::components.page.page-tree'),
            ]);
    }
}
