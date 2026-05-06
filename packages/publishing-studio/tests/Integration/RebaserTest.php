<?php

declare(strict_types=1);

use Capell\PublishingStudio\Models\Version;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Rebaser;
use Capell\PublishingStudio\Tests\Integration\Fixtures\WorkspaceDraftableFixture;
use Capell\PublishingStudio\WorkspaceRegistry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Schema::create('workspace_draftable_fixtures', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('workspace_id')->default(0)->index();
        $table->uuid('uuid');
        $table->string('name');
        $table->timestamps();
    });

    WorkspaceRegistry::reset();
    WorkspaceRegistry::register(WorkspaceDraftableFixture::class);
});

afterEach(function (): void {
    Schema::dropIfExists('workspace_draftable_fixtures');
    WorkspaceRegistry::reset();
});

function publishAdditionalLiveVersion(string $name = 'Newer Live'): Version
{
    $version = Version::query()->create([
        'uuid' => (string) Str::uuid(),
        'number' => (Version::query()->max('number') ?? 0) + 1,
        'name' => $name,
        'is_live' => false,
        'manifest' => [],
        'published_at' => now(),
    ]);

    Version::query()->where('id', '!=', $version->id)->update(['is_live' => false]);
    $version->is_live = true;
    $version->save();

    return $version;
}

it('returns an empty, non-stale report when the workspace is on the current live version', function (): void {
    $workspace = Workspace::factory()->create([
        'base_version_id' => Version::liveId(),
    ]);

    $rebaser = new Rebaser;
    $report = $rebaser->analyse($workspace);

    expect($report->hasConflicts())->toBeFalse()
        ->and($report->isStale())->toBeFalse()
        ->and($report->conflictCount())->toBe(0)
        ->and($report->currentLiveVersionId)->toBe(Version::liveId());
});

it('detects conflicts when a live row was updated after the workspace updated_at', function (): void {
    $originalLiveId = Version::liveId();
    $workspace = Workspace::factory()->create([
        'base_version_id' => $originalLiveId,
    ]);

    $sharedUuid = (string) Str::uuid();
    $untouchedUuid = (string) Str::uuid();

    WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => $workspace->id,
            'uuid' => $sharedUuid,
            'name' => 'workspace-copy',
        ]);
    WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => $workspace->id,
            'uuid' => $untouchedUuid,
            'name' => 'workspace-untouched',
        ]);

    DB::table('workspace_draftable_fixtures')->insert([
        'workspace_id' => 0,
        'uuid' => $sharedUuid,
        'name' => 'live-updated-after',
        'created_at' => now()->subMinute(),
        'updated_at' => now()->addMinute(),
    ]);
    DB::table('workspace_draftable_fixtures')->insert([
        'workspace_id' => 0,
        'uuid' => $untouchedUuid,
        'name' => 'live-older',
        'created_at' => now()->subMinute(),
        'updated_at' => now()->subMinute(),
    ]);

    publishAdditionalLiveVersion();

    $rebaser = new Rebaser;
    $report = $rebaser->analyse($workspace->refresh());

    expect($report->isStale())->toBeTrue()
        ->and($report->hasConflicts())->toBeTrue()
        ->and($report->conflictCount())->toBe(1)
        ->and($report->conflicts())->toHaveKey(WorkspaceDraftableFixture::class)
        ->and($report->conflicts()[WorkspaceDraftableFixture::class])->toBe([$sharedUuid]);
});

it('returns no conflicts when the workspace has no rows for a registered model', function (): void {
    $workspace = Workspace::factory()->create([
        'base_version_id' => Version::liveId(),
    ]);

    publishAdditionalLiveVersion();

    $rebaser = new Rebaser;
    $report = $rebaser->analyse($workspace->refresh());

    expect($report->isStale())->toBeTrue()
        ->and($report->hasConflicts())->toBeFalse();
});

it('fast-forwards base_version_id to the current live version', function (): void {
    $originalLiveId = Version::liveId();
    $workspace = Workspace::factory()->create([
        'base_version_id' => $originalLiveId,
    ]);

    $newerLive = publishAdditionalLiveVersion();

    $rebaser = new Rebaser;
    $updated = $rebaser->fastForward($workspace);

    expect($updated->base_version_id)->toBe($newerLive->id)
        ->and($workspace->fresh()->base_version_id)->toBe($newerLive->id);
});
