<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Layout;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Enums\LivewireComponentsEnum;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class LayoutTab extends Tab
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-layout-builder::tab.layout'))
            ->visibleOn(['edit', 'editOption'])
            ->icon(Heroicon::OutlinedViewColumns)
            ->schema(fn (?Layout $record): array => $record instanceof Layout ? [
                Livewire::make(
                    LivewireComponentsEnum::LayoutBuilder->value,
                    fn (Layout $record): array => [
                        'site' => $record->site,
                        'layout' => $record,
                    ],
                )->lazy(),
            ] : []);
    }
}
