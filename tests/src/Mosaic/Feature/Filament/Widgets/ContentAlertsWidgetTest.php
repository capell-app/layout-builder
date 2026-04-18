<?php

declare(strict_types=1);

use Illuminate\Support\Collection as SupportCollection;

use function Pest\Livewire\livewire;

it('renders the content alerts widget', function (): void {
    $content = Collection::factory()->create();

    livewire(ContentAlertsWidget::class, ['record' => $content])
        ->assertSuccessful();
});

it('shows alert for content state', function (string $state, string $alertKey): void {
    $content = Collection::factory()
        ->when(
            $state === 'expired',
            fn (CollectionFactory $factory): CollectionFactory => $factory->expired(),
        )
        ->when(
            $state === 'pending',
            fn (CollectionFactory $factory): CollectionFactory => $factory->pending(),
        )
        ->when(
            $state === 'trashed',
            fn (CollectionFactory $factory): CollectionFactory => $factory->trashed(),
        )
        ->create();

    livewire(ContentAlertsWidget::class, ['record' => $content])
        ->assertSuccessful()
        ->assertSet('alerts', fn (SupportCollection $alerts): bool => $alerts->has($alertKey));
})
    ->with([
        'expired' => ['expired', 'expired'],
        'pending' => ['pending', 'pending'],
        'trashed' => ['trashed', 'trashed'],
    ]);

test('does not show alert for published content', function (): void {
    $content = Collection::factory()->published()->create();

    livewire(ContentAlertsWidget::class, ['record' => $content])
        ->assertSuccessful()
        ->assertSet('alerts', fn (Collection $alerts): bool => $alerts->isEmpty());
});
