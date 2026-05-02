<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Components\Forms\Layout;

use Capell\Core\Models\Layout;
use Capell\Mosaic\Enums\LivewireComponentsEnum;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class LayoutTab extends Tab
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-mosaic::tab.layout'))
            ->visibleOn(['edit', 'editOption'])
            ->icon(Heroicon::OutlinedPuzzlePiece)
            ->schema(fn (?Layout $record): array => $record instanceof Layout ? [
                Livewire::make(
                    LivewireComponentsEnum::LayoutBuilder->value,
                    fn (Layout $record): array => [
                        'site' => $record->site,
                        'layout' => $record,
                    ],
                ),
            ] : []);
    }
}
