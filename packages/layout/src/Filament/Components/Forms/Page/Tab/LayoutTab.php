<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Page\Tab;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Layout\Enums\LivewireComponentsEnum;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class LayoutTab
{
    public static function make(): Tab
    {
        return Tab::make(__('capell-admin::tab.layout'))
            ->icon(Heroicon::OutlinedPuzzlePiece)
            ->visible(fn (Get $get, Pageable $record): bool => (bool) ($get('layout_id') ?? $record->layout_id))
            ->schema([
                Livewire::make(
                    LivewireComponentsEnum::LayoutBuilder->value,
                    function (Get $get, Pageable $record): array {
                        $layout = $record->layout;

                        if ($get('layout_id') !== null && $layout->id !== $get('layout_id')) {
                            /** @var class-string<Layout> $model */
                            $model = CapellCore::getModel(ModelEnum::Layout);

                            $layout = $model::query()->find($get('layout_id'));
                        }

                        return [
                            'site' => $record->site,
                            'layout' => $layout,
                            'page' => $record,
                        ];
                    },
                )
                    ->lazy(config('capell-layout.layout_builder.lazy', true))
                    ->columnSpanFull(),
            ]);
    }
}
