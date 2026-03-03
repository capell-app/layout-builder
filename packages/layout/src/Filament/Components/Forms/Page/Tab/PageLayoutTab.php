<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Page\Tab;

use Capell\Core\Models\Page;
use Capell\Layout\Enums\LivewireComponentsEnum;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class PageLayoutTab
{
    public static function make(): Tab
    {
        return Tab::make(__('capell-admin::tab.layout'))
            ->icon(Heroicon::OutlinedPuzzlePiece)
            ->visible(fn (Get $get, Page $record): bool => (bool) ($get('layout_id') ?? $record->layout_id))
            ->schema([
                Livewire::make(
                    LivewireComponentsEnum::LayoutBuilder->value,
                    fn (Get $get, Page $record): array => [
                        'site_id' => $record->site_id,
                        'layout_id' => $get('layout_id') ?? $record->layout_id,
                        'page_id' => $record->id,
                    ],
                )
                    ->lazy(config('capell-layout.layout_builder.lazy', true))
                    ->columnSpanFull(),
            ]);
    }
}
