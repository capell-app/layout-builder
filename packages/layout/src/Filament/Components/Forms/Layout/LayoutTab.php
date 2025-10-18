<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Layout;

use Capell\Core\Models\Layout;
use Capell\Layout\Livewire\LayoutBuilder;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class LayoutTab extends Tab
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::tab.layout_builder'))
            ->visibleOn(['edit', 'editOption'])
            ->icon(Heroicon::OutlinedPuzzlePiece)
            ->schema([
                Livewire::make(
                    LayoutBuilder::class,
                    fn (Get $get, Layout $record): array => [
                        'site_id' => $record->site_id,
                        'layout_id' => $record->id,
                    ]
                ),
            ]);
    }
}
