<?php

declare(strict_types=1);

use Capell\Tests\Fixtures\Models\User;
use Capell\Workspaces\Models\Version;
use Capell\Workspaces\Models\Workspace;
use Illuminate\Support\Str;

it('has a bootstrap live version available out of the box', function (): void {
    $currentLive = Version::currentLive();

    expect($currentLive)->not->toBeNull()
        ->and($currentLive->is_live)->toBeTrue()
        ->and($currentLive->number)->toBe(1)
        ->and(Version::liveId())->toBe($currentLive->id);
});

it('returns the live version id and model via the currentLive helper', function (): void {
    $live = Version::currentLive();

    expect($live->id)->toBe(Version::liveId());
});

it('manifest is persisted as an array and queryable', function (): void {
    $version = Version::query()->create([
        'uuid' => (string) Str::uuid(),
        'number' => Version::query()->max('number') + 1,
        'name' => 'Spring',
        'is_live' => false,
        'manifest' => [
            'App\\Models\\Page' => [1, 2, 3],
            'App\\Models\\Navigation' => [10],
        ],
        'published_at' => now(),
    ]);

    $version->refresh();

    expect($version->manifest)->toBe([
        'App\\Models\\Page' => [1, 2, 3],
        'App\\Models\\Navigation' => [10],
    ])->and($version->manifestIdsFor('App\\Models\\Page'))->toBe([1, 2, 3])
        ->and($version->manifestIdsFor('App\\Models\\Missing'))->toBe([]);
});

it('tracks the source workspace relationship when published', function (): void {
    $workspace = Workspace::factory()->create();

    $version = Version::query()->create([
        'uuid' => (string) Str::uuid(),
        'number' => Version::query()->max('number') + 1,
        'name' => 'Published From Workspace',
        'is_live' => false,
        'manifest' => [],
        'source_workspace_id' => $workspace->id,
        'published_at' => now(),
    ]);

    expect($version->sourceWorkspace)->toBeInstanceOf(Workspace::class)
        ->and($version->sourceWorkspace->id)->toBe($workspace->id);
});

it('records polymorphic publishedBy when a user publishes the version', function (): void {
    $publisher = User::factory()->create();

    $version = Version::query()->create([
        'uuid' => (string) Str::uuid(),
        'number' => Version::query()->max('number') + 1,
        'name' => 'Manual Publish',
        'is_live' => false,
        'manifest' => [],
        'published_by_type' => $publisher->getMorphClass(),
        'published_by_id' => $publisher->getKey(),
        'published_at' => now(),
    ]);

    expect($version->publishedBy)->not->toBeNull()
        ->and($version->publishedBy->getKey())->toBe($publisher->getKey());
});
