<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Filament\Resources\Sections\SectionResource;
use Capell\LayoutBuilder\Filament\Resources\Sections\Widgets\SectionAlertsWidget;
use Capell\LayoutBuilder\Models\Section;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\get;

uses(CreatesAdminUser::class);

test('see livewire component', function (): void {
    test()->actingAsAdmin();

    $content = Section::factory()->create();

    get(SectionResource::getUrl('edit', ['record' => $content]))
        ->assertSeeLivewire(SectionAlertsWidget::class);
});
