<?php

declare(strict_types=1);

use Capell\Admin\Actions\ReplicatePageAction;
use Capell\Core\Actions\PageSavedAction;
use Capell\Core\Models\Page;
use Capell\Workspaces\Filament\Widgets\WorkspaceMergeHistoryWidgetAbstract as WorkspaceMergeHistoryWidget;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::flush();
});

it('invalidates workspace merge history cache when replicating a page', function (): void {
    $page = Page::factory()->create();

    // Cache workspace merge history
    $widget = new WorkspaceMergeHistoryWidget;
    $cachedData = $widget->data();
    expect(Cache::has('dashboard:workspace-merge-history'))->toBeTrue();

    // Replicate should clear cache
    ReplicatePageAction::run($page);

    // Verify cache was invalidated
    expect(Cache::has('dashboard:workspace-merge-history'))->toBeFalse();
});

it('invalidates workspace merge history cache when saving a page', function (): void {
    $page = Page::factory()->create();

    // Cache workspace merge history
    $widget = new WorkspaceMergeHistoryWidget;
    $cachedData = $widget->data();
    expect(Cache::has('dashboard:workspace-merge-history'))->toBeTrue();

    // Save should clear cache
    PageSavedAction::run($page);

    // Verify cache was invalidated
    expect(Cache::has('dashboard:workspace-merge-history'))->toBeFalse();
});
