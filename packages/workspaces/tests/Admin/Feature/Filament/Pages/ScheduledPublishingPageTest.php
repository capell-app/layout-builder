<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Workspaces\Filament\Pages\ScheduledPublishingPage;
use Capell\Workspaces\Models\Workspace;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('lists page and workspace scheduler events', function (): void {
    Page::factory()->create([
        'visible_from' => now()->addDays(3),
        'name' => 'Spring sale',
    ]);
    Page::factory()->create([
        'visible_from' => now()->subMonth(),
        'visible_until' => now()->addWeek(),
        'name' => 'Holiday banner',
    ]);
    Page::factory()->create([
        'visible_from' => now()->subMonth(),
        'visible_until' => null,
        'name' => 'About',
    ]);
    Workspace::factory()->scheduled(now()->addDays(5))->create([
        'name' => 'Campaign workspace',
        'review_reminder_at' => now()->addDays(2),
    ]);

    livewire(ScheduledPublishingPage::class)
        ->assertSee('Spring sale')
        ->assertSee('Holiday banner')
        ->assertSee('Campaign workspace')
        ->assertSee('Review Reminder')
        ->assertDontSee('About');
});

test('uses content scheduler labels and prominent navigation', function (): void {
    expect(ScheduledPublishingPage::getSlug())->toBe('scheduled-publishing');
    expect(ScheduledPublishingPage::getNavigationLabel())->toBe('Content Scheduler')
        ->and(ScheduledPublishingPage::getNavigationGroup())->toBe('Content');
});

test('shows a navigation badge for upcoming scheduler events', function (): void {
    Page::factory()->create([
        'visible_from' => now()->addDays(3),
        'name' => 'Spring sale',
    ]);

    expect(ScheduledPublishingPage::getNavigationBadge())->toBe('1');
});
