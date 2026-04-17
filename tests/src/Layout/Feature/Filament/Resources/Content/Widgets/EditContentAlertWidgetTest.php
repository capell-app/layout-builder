<?php

declare(strict_types=1);

use Capell\Layout\Filament\Resources\Collections\CollectionResource;
use Capell\Layout\Filament\Resources\Collections\Widgets\ContentAlertsWidget;
use Capell\Layout\Models\Collection;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\get;

uses(CreatesAdminUser::class);

test('see livewire component', function (): void {
    test()->actingAsAdmin();

    $content = Collection::factory()->create();

    get(CollectionResource::getUrl('edit', ['record' => $content]))
        ->assertSeeLivewire(ContentAlertsWidget::class);
});
