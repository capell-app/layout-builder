<?php

declare(strict_types=1);

use Capell\Layout\Filament\Resources\Contents\ContentResource;
use Capell\Layout\Filament\Resources\Contents\Widgets\ContentAlertsWidget;
use Capell\Layout\Models\Content;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\get;

uses(CreatesAdminUser::class);

test('see livewire component', function (): void {
    test()->actingAsAdmin();

    $content = Content::factory()->create();

    get(ContentResource::getUrl('edit', ['record' => $content]))
        ->assertSeeLivewire(ContentAlertsWidget::class);
});
