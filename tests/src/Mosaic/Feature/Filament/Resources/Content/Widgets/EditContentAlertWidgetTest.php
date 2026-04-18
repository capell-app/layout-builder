<?php

declare(strict_types=1);

use Capell\Mosaic\Filament\Resources\Sections\ContentResource;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\get;

uses(CreatesAdminUser::class);

test('see livewire component', function (): void {
    test()->actingAsAdmin();

    $content = Collection::factory()->create();

    get(ContentResource::getUrl('edit', ['record' => $content]))
        ->assertSeeLivewire(ContentAlertsWidget::class);
});
