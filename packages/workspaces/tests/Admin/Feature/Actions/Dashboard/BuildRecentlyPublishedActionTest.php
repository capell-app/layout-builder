<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Workspaces\Actions\Dashboard\BuildRecentlyPublishedAction;
use Capell\Workspaces\Models\Workspace;

it('returns the N most recent published pages', function (): void {
    $workspace = Workspace::factory()->published()->create();

    Page::factory()->count(15)->create(['workspace_id' => $workspace->id]);

    $result = BuildRecentlyPublishedAction::run(10);

    expect($result->items->count())->toBe(10);
});

it('orders by published_at descending', function (): void {
    $older = Workspace::factory()->published()->create([
        'published_at' => now()->subDays(5),
    ]);
    $newer = Workspace::factory()->published()->create([
        'published_at' => now()->subDay(),
    ]);

    $olderPage = Page::factory()->create(['workspace_id' => $older->id]);
    $newerPage = Page::factory()->create(['workspace_id' => $newer->id]);

    $result = BuildRecentlyPublishedAction::run(10);
    $ids = $result->items->toCollection()->pluck('pageId');

    expect($ids->first())->toBe($newerPage->id)
        ->and($ids->last())->toBe($olderPage->id);
});

it('excludes pages from draft (unpublished) workspaces', function (): void {
    $draft = Workspace::factory()->open()->create();
    Page::factory()->create(['workspace_id' => $draft->id]);

    $result = BuildRecentlyPublishedAction::run(10);

    expect($result->items->count())->toBe(0);
});

it('excludes pages from future-scheduled workspaces', function (): void {
    $scheduled = Workspace::factory()->scheduled(now()->addHour())->create();
    Page::factory()->create(['workspace_id' => $scheduled->id]);

    $result = BuildRecentlyPublishedAction::run(10);

    expect($result->items->count())->toBe(0);
});

it('filters by site when $site is provided', function (): void {
    $siteA = Site::factory()->withTranslations()->create();
    $siteB = Site::factory()->withTranslations()->create();

    $workspaceA = Workspace::factory()->published()->create();
    $workspaceB = Workspace::factory()->published()->create();

    $pageA = Page::factory()->site($siteA)->create(['workspace_id' => $workspaceA->id]);
    Page::factory()->site($siteB)->create(['workspace_id' => $workspaceB->id]);

    $result = BuildRecentlyPublishedAction::run(10, $siteA);
    $ids = $result->items->toCollection()->pluck('pageId');

    expect($ids)->toContain($pageA->id)
        ->and($ids->count())->toBe(1);
});

it('includes site name in each item', function (): void {
    $site = Site::factory()->withTranslations()->create(['name' => 'Acme Corp']);
    $workspace = Workspace::factory()->published()->create();

    Page::factory()->site($site)->create(['workspace_id' => $workspace->id]);

    $result = BuildRecentlyPublishedAction::run(10);

    expect($result->items->toCollection()->first()->siteName)->toBe('Acme Corp');
});
