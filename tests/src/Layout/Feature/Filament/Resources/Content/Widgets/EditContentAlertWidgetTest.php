<?php

declare(strict_types=1);

use Capell\Mosaic\Filament\Resources\Sections\SectionResource;
use Capell\Mosaic\Filament\Resources\Sections\Widgets\ContentAlertsWidget;
use Capell\Mosaic\Models\Section;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\get;

uses(CreatesAdminUser::class);

test('see livewire component', function (): void {
    test()->actingAsAdmin();

    $content = Section::factory()->create();

    get(SectionResource::getUrl('edit', ['record' => $content]))
        ->assertSeeLivewire(ContentAlertsWidget::class);
});
