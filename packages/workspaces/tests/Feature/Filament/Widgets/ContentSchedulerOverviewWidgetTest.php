<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Workspaces\Filament\Widgets\ContentSchedulerOverviewWidget;
use Capell\Workspaces\Models\Workspace;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('widget');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('renders scheduler event counts', function (): void {
    Page::factory()->create([
        'name' => 'Launch page',
        'visible_from' => now()->addDay(),
        'visible_until' => now()->addDays(10),
    ]);
    Workspace::factory()->scheduled(now()->addDays(2))->create([
        'name' => 'Launch workspace',
        'embargo_until' => now()->addDay(),
        'review_reminder_at' => now()->addHours(6),
    ]);

    livewire(ContentSchedulerOverviewWidget::class)
        ->assertOk()
        ->assertSee('Publish')
        ->assertSee('Unpublish')
        ->assertSee('Embargo')
        ->assertSee('Review Reminder');
});
