<?php

declare(strict_types=1);

use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Filament\Pages\StaleDraftsPage;
use Capell\PublishingStudio\Models\Workspace;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('lists stale publishing-studio and hides fresh ones', function (): void {
    $stale = Workspace::factory()->create([
        'status' => WorkspaceStatusEnum::Open->value,
        'updated_at' => now()->subDays(30),
        'name' => 'Stale campaign draft',
    ]);

    $fresh = Workspace::factory()->create([
        'status' => WorkspaceStatusEnum::Open->value,
        'updated_at' => now()->subDays(2),
        'name' => 'Fresh draft',
    ]);

    livewire(StaleDraftsPage::class)
        ->assertCanSeeTableRecords([$stale])
        ->assertCanNotSeeTableRecords([$fresh]);
});

test('exposes the correct slug and navigation group', function (): void {
    expect(StaleDraftsPage::getSlug())->toBe('stale-drafts');
    expect(StaleDraftsPage::getNavigationGroup())
        ->toBe((string) __('capell-admin::navigation.group_content'));
});
