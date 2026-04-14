<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Layout;

use Capell\Core\Models\Layout;
use Capell\Layout\Enums\LivewireComponentsEnum;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class LayoutTab extends Tab
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-layout::tab.layout'))
            ->visibleOn(['edit', 'editOption'])
            ->icon(Heroicon::OutlinedPuzzlePiece)
            ->schema([
                Livewire::make(
                    LivewireComponentsEnum::LayoutBuilder->value,
                    fn (Get $get, Layout $record): array => [
                        'site' => $record->site,
                        'layout' => $record,
                    ],
                ),
            ]);
    }
}
