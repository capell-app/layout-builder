<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Database\Factories\SectionFactory;
use Capell\LayoutBuilder\Filament\Resources\Sections\Widgets\SectionAlertsWidget;
use Capell\LayoutBuilder\Models\Section;
use Illuminate\Support\Collection;

use function Pest\Livewire\livewire;

it('renders the content alerts widget', function (): void {
    $content = Section::factory()->create();

    livewire(SectionAlertsWidget::class, ['record' => $content])
        ->assertSuccessful();
});

it('shows alert for content state', function (string $state, string $alertKey): void {
    $content = Section::factory()
        ->when(
            $state === 'expired',
            fn (SectionFactory $factory): SectionFactory => $factory->expired(),
        )
        ->when(
            $state === 'pending',
            fn (SectionFactory $factory): SectionFactory => $factory->pending(),
        )
        ->when(
            $state === 'trashed',
            fn (SectionFactory $factory): SectionFactory => $factory->trashed(),
        )
        ->create();

    livewire(SectionAlertsWidget::class, ['record' => $content])
        ->assertSuccessful()
        ->assertSet('alerts', fn (Collection $alerts): bool => $alerts->has($alertKey));
})
    ->with([
        'expired' => ['expired', 'expired'],
        'pending' => ['pending', 'pending'],
        'trashed' => ['trashed', 'trashed'],
    ]);

test('does not show alert for published content', function (): void {
    $content = Section::factory()->published()->create();

    livewire(SectionAlertsWidget::class, ['record' => $content])
        ->assertSuccessful()
        ->assertSet('alerts', fn (Collection $alerts): bool => $alerts->isEmpty());
});
