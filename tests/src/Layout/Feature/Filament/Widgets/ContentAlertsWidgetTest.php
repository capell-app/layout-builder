<?php

declare(strict_types=1);

use Capell\Layout\Database\Factories\ContentFactory;
use Capell\Layout\Filament\Resources\Contents\ContentResource;
use Capell\Layout\Filament\Resources\Contents\Pages\EditContent;
use Capell\Layout\Filament\Resources\Contents\Widgets\ContentAlertsWidget;
use Capell\Layout\Models\Content;
use Illuminate\Support\Collection;

use function Pest\Livewire\livewire;

it('renders the content alerts widget', function (): void {
    $content = Content::factory()->create();

    livewire(ContentAlertsWidget::class, ['record' => $content])
        ->assertSuccessful();
});

it('shows alert for content state', function (string $state, string $alertKey): void {
    $content = Content::factory()
        ->when(
            $state === 'draft',
            fn (ContentFactory $factory): ContentFactory => $factory->draft(),
        )
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
        ->assertSet('alerts', fn (Collection $alerts): bool => $alerts->has($alertKey));
})
    ->with([
        'draft' => ['draft', 'draft'],
        'expired' => ['expired', 'expired'],
        'pending' => ['pending', 'pending'],
        'trashed' => ['trashed', 'trashed'],
    ]);

test('does not show alert for published content', function (): void {
    $content = Content::factory()->published()->create();

    livewire(ContentAlertsWidget::class, ['record' => $content])
        ->assertSuccessful()
        ->assertSet('alerts', fn (Collection $alerts): bool => $alerts->isEmpty());
});

test('publish draft content', function (): void {
    $content = Content::factory()->draft()->create();

    livewire(ContentAlertsWidget::class, ['record' => $content])
        ->callAction('publishAction')
        ->assertDispatchedTo(EditContent::class, '$refresh');

    expect($content->refresh())->isPublished()->toBeTrue();
});

test('delete draft content redirect to list', function (): void {
    $content = Content::factory()->draft()->create();

    livewire(ContentAlertsWidget::class, ['record' => $content])
        ->callAction('deleteDraftAction')
        ->assertRedirect(ContentResource::getUrl());
});

test('delete draft content redirect to current', function (): void {
    $current = Content::factory()->create();
    $content = Content::factory()->draft()->state(['uuid' => $current->uuid])->create();

    livewire(ContentAlertsWidget::class, ['record' => $content])
        ->callAction('deleteDraftAction')
        ->assertRedirect(ContentResource::getUrl('edit', ['record' => $current->getKey()]));
});
