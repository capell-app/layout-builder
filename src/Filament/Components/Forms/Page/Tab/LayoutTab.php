<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Page\Tab;

use Capell\Core\Contracts\Pageable;
use Capell\Core\LayoutBuilder\Enums\LivewireComponentsEnum;
use Capell\Core\Models\Layout;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class LayoutTab
{
    public static function make(): Tab
    {
        return Tab::make(__('capell-admin::tab.layout'))
            ->icon(Heroicon::OutlinedPuzzlePiece)
            ->visible(fn (Get $get, ?Pageable $record = null): bool => (bool) ($get('layout_id') ?? $record?->layout_id))
            ->schema([
                Livewire::make(
                    LivewireComponentsEnum::LayoutBuilder->value,
                    function (Get $get, ?Pageable $record = null): array {
                        if (! $record instanceof Pageable) {
                            return [];
                        }

                        $layout = $record->layout;

                        if ($get('layout_id') !== null && $layout->id !== $get('layout_id')) {
                            /** @var class-string<Layout> $model */
                            $model = Layout::class;

                            $layout = $model::query()
                                ->where(
                                    fn (Builder $query): Builder => $query
                                        ->whereNull('site_id')
                                        ->orWhere('site_id', $record->site_id),
                                )
                                ->find($get('layout_id')) ?? $layout;
                        }

                        return [
                            'site' => $record->site,
                            'layout' => $layout,
                            'page' => $record,
                        ];
                    },
                )
                    ->lazy(config('capell-layout-builder.layout_builder.lazy', true))
                    ->columnSpanFull(),
            ]);
    }
}
