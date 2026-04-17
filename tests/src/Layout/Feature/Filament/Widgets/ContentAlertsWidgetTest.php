<?php

declare(strict_types=1);

use Capell\Layout\Database\Factories\ContentFactory;
use Capell\Layout\Filament\Resources\Contents\Widgets\ContentAlertsWidget;
use Illuminate\Support\Collection as SupportCollection;

use function Pest\Livewire\livewire;

it('renders the content alerts widget', function (): void {
    $content = Content::factory()->create();

    livewire(ContentAlertsWidget::class, ['record' => $content])
        ->assertSuccessful();
});

it('shows alert for content state', function (string $state, string $alertKey): void {
    $content = Content::factory()
        ->when(
            $state === 'expired',
            fn (ContentFactory $factory): ContentFactory => $factory->expired(),
        )
        ->when(
            $state === 'pending',
            fn (ContentFactory $factory): ContentFactory => $factory->pending(),
        )
        ->when(
            $state === 'trashed',
            fn (ContentFactory $factory): ContentFactory => $factory->trashed(),
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
    $content = Content::factory()->published()->create();

    livewire(ContentAlertsWidget::class, ['record' => $content])
        ->assertSuccessful()
        ->assertSet('alerts', fn (SupportCollection $alerts): bool => $alerts->isEmpty());
});
