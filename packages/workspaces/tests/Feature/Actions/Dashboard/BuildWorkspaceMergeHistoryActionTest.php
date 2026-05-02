<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Workspaces\Actions\Dashboard\BuildWorkspaceMergeHistoryAction;
use Capell\Workspaces\Models\Workspace;

uses(CreatesAdminUser::class);

it('returns entries in descending published_at order', function (): void {
    $workspace1 = Workspace::factory()->published()->create(['published_at' => now()->subHours(5)]);
    $workspace2 = Workspace::factory()->published()->create(['published_at' => now()->subHours(2)]);
    $workspace3 = Workspace::factory()->published()->create(['published_at' => now()->subHours(10)]);

    $result = BuildWorkspaceMergeHistoryAction::run(30);
    $ids = $result->entries->toCollection()->pluck('workspaceId')->take(3)->all();

    expect($ids)->toBe([
        $workspace2->id,
        $workspace1->id,
        $workspace3->id,
    ]);
});

it('respects the limit parameter', function (): void {
    Workspace::factory()->published()->count(10)->create(['published_at' => now()]);

    $result = BuildWorkspaceMergeHistoryAction::run(3);

    expect($result->entries->count())->toBe(3);
});

it('uses the default limit of 30', function (): void {
    Workspace::factory()->published()->count(5)->create(['published_at' => now()]);

    $result = BuildWorkspaceMergeHistoryAction::run();

    expect($result->entries->count())->toBeLessThanOrEqual(30);
});

it('returns correct page count per workspace', function (): void {
    $workspace = Workspace::factory()->published()->create(['published_at' => now()]);
    Page::factory()->count(4)->create(['workspace_id' => $workspace->id]);

    $result = BuildWorkspaceMergeHistoryAction::run(30);

    $entry = $result->entries->toCollection()
        ->first(fn (object $item): bool => $item->workspaceId === $workspace->id);

    expect($entry)->not->toBeNull()
        ->and($entry->pageCount)->toBe(4);
});

it('includes actor name when workspace has a creator', function (): void {
    $actor = $this->createUser(['name' => 'Alice Publisher']);

    $workspace = Workspace::factory()->published()->create([
        'published_at' => now(),
        'created_by' => $actor->id,
    ]);

    $result = BuildWorkspaceMergeHistoryAction::run(30);

    $entry = $result->entries->toCollection()
        ->first(fn (object $item): bool => $item->workspaceId === $workspace->id);

    expect($entry)->not->toBeNull()
        ->and($entry->actorName)->toBe('Alice Publisher');
});

it('calculates duration open hours from created_at to published_at', function (): void {
    $publishedAt = now();
    $createdAt = now()->subDays(2);

    $workspace = Workspace::factory()->published()->create([
        'published_at' => $publishedAt,
    ]);
    $workspace->forceFill(['created_at' => $createdAt])->save();

    $result = BuildWorkspaceMergeHistoryAction::run(30);

    $entry = $result->entries->toCollection()
        ->first(fn (object $item): bool => $item->workspaceId === $workspace->id);

    expect($entry)->not->toBeNull()
        ->and($entry->durationOpenHours)->toBeGreaterThanOrEqual(2 * 24);
});

it('returns an empty collection when no published workspaces exist', function (): void {
    $result = BuildWorkspaceMergeHistoryAction::run(30);

    expect($result->entries->count())->toBe(0);
});
