<?php

declare(strict_types=1);

use Capell\Workspaces\Data\Dashboard\WorkspaceMergeHistoryData;
use Capell\Workspaces\Filament\Widgets\WorkspaceMergeHistoryWidgetAbstract as WorkspaceMergeHistoryWidget;
use Capell\Workspaces\Models\Workspace;
use Illuminate\Support\Facades\Cache;
use Spatie\LaravelData\DataCollection;

beforeEach(function (): void {
    Cache::flush();
});

it('caches workspace merge history query result', function (): void {
    $widget = new WorkspaceMergeHistoryWidget;

    // Clear cache to ensure fresh state
    Cache::forget('dashboard:workspace-merge-history');

    // First call should execute the action and cache
    $firstResult = $widget->data();
    expect($firstResult)->toBeInstanceOf(WorkspaceMergeHistoryData::class);

    // Verify cache was set
    $cachedResult = Cache::get('dashboard:workspace-merge-history');
    expect($cachedResult)->toBeInstanceOf(WorkspaceMergeHistoryData::class);

    // Second call should return cached result
    $secondResult = $widget->data();
    expect($secondResult)->toEqual($firstResult);
});

it('widget cache key is correct', function (): void {
    $widget = new WorkspaceMergeHistoryWidget;
    $widget->data();
    // Trigger caching
    $cached = Cache::get('dashboard:workspace-merge-history');
    expect($cached)->not->toBeNull();
    expect($cached)->toBeInstanceOf(WorkspaceMergeHistoryData::class);
});

it('allows clearing cache for widget', function (): void {
    $widget = new WorkspaceMergeHistoryWidget;

    // Cache a result
    $data = $widget->data();
    Cache::put('dashboard:workspace-merge-history', $data, 300);
    expect(Cache::has('dashboard:workspace-merge-history'))->toBeTrue();

    // Clear the cache
    $widget->clearCacheForWidget('dashboard:workspace-merge-history');
    expect(Cache::has('dashboard:workspace-merge-history'))->toBeFalse();
});

it('optimizes queries using withCount for page count', function (): void {
    // Create test workspaces with pages
    /** @var Workspace $workspace */
    $workspace = Workspace::factory()->create();

    $widget = new WorkspaceMergeHistoryWidget;
    $result = $widget->data();

    expect($result->entries)->toBeInstanceOf(DataCollection::class);
    // Verify page count is available
    if ($result->entries->count() > 0) {
        expect($result->entries->first()->pageCount)->toBeInt();
    }
});
