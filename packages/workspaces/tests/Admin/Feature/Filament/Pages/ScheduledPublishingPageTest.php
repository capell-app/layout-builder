<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Workspaces\Filament\Pages\ScheduledPublishingPage;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('lists pages pending publish and pending unpublish', function (): void {
    $pendingPublish = Page::factory()->create([
        'visible_from' => now()->addDays(3),
        'name' => 'Spring sale',
    ]);
    $pendingUnpublish = Page::factory()->create([
        'visible_from' => now()->subMonth(),
        'visible_until' => now()->addWeek(),
        'name' => 'Holiday banner',
    ]);
    $alreadyPublished = Page::factory()->create([
        'visible_from' => now()->subMonth(),
        'visible_until' => null,
        'name' => 'About',
    ]);

    livewire(ScheduledPublishingPage::class)
        ->assertCanSeeTableRecords([$pendingPublish, $pendingUnpublish])
        ->assertCanNotSeeTableRecords([$alreadyPublished]);
});

test('uses the correct slug and navigation group', function (): void {
    expect(ScheduledPublishingPage::getSlug())->toBe('scheduled-publishing');
    expect(ScheduledPublishingPage::getNavigationGroup())
        ->toBe((string) __('capell-admin::navigation.group_monitoring'));
});
