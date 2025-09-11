<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Page\Tab;

use Capell\Core\Models\Page;
use Capell\Layout\Livewire\LayoutBuilder;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;

class PageLayoutTab
{
    public static function make(): Tab
    {
        return Tab::make(__('capell-admin::tab.layout'))
            ->icon('heroicon-o-puzzle-piece')
            ->visible(fn (Get $get, Page $record): bool => (bool) ($get('layout_id') ?: $record->layout_id))
            ->schema([
                Livewire::make(
                    LayoutBuilder::class,
                    fn (Get $get, Page $record): array => [
                        'site_id' => $record->site_id,
                        'layout_id' => $get('layout_id') ?: $record->layout_id,
                        'page_id' => $record->id,
                    ]
                )
                    ->columnSpanFull(),
            ]);
    }
}
