<?php

declare(strict_types=1);

use Capell\Workspaces\Events\VersionRolledBack;
use Capell\Workspaces\Models\Version;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Publisher;
use Capell\Workspaces\Rollback;
use Capell\Workspaces\Tests\Integration\Fixtures\WorkspaceDraftableFixture;
use Capell\Workspaces\WorkspaceRegistry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Schema::create('workspace_draftable_fixtures', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('workspace_id')->default(0)->index();
        $table->unsignedBigInteger('shadowed_by_workspace_id')->default(0)->index();
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

function publishWorkspaceWithFixture(string $uuid, string $name): Version
{
    $workspace = Workspace::factory()->approved()->create();

    WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => $workspace->id,
            'uuid' => $uuid,
            'name' => $name,
        ]);

    return (new Publisher)->publish($workspace);
}

it('rolls back to the previous live version and promotes it', function (): void {
    $firstLiveId = Version::liveId();
    $firstVersion = publishWorkspaceWithFixture((string) Str::uuid(), 'first-release');
    $secondVersion = publishWorkspaceWithFixture((string) Str::uuid(), 'second-release');

    $rollbackRecord = (new Rollback)->rollbackTo($firstVersion, reason: 'regression');

    expect(Version::liveId())->toBe($firstVersion->id)
        ->and($secondVersion->fresh()->is_live)->toBeFalse()
        ->and($rollbackRecord->is_live)->toBeFalse()
        ->and($rollbackRecord->rollback_of_version_id)->toBe($firstVersion->id)
        ->and($rollbackRecord->name)->toContain('Rollback')
        ->and($rollbackRecord->notes)->toBe('regression')
        ->and($firstLiveId)->not->toBeNull();
});

it('deletes rows that were introduced by publishes after the target', function (): void {
    $keeperUuid = (string) Str::uuid();
    $extraUuid = (string) Str::uuid();

    $targetVersion = publishWorkspaceWithFixture($keeperUuid, 'keeper');
    publishWorkspaceWithFixture($extraUuid, 'extra');

    (new Rollback)->rollbackTo($targetVersion);

    $liveUuids = WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->where('workspace_id', 0)
        ->pluck('uuid')
        ->all();

    expect($liveUuids)->toContain($keeperUuid)
        ->and($liveUuids)->not->toContain($extraUuid);
});

it('rejects rolling to the current live version', function (): void {
    $liveVersion = publishWorkspaceWithFixture((string) Str::uuid(), 'live');

    (new Rollback)->rollbackTo($liveVersion);
})->throws(LogicException::class);

it('rejects rolling to a version that was never published', function (): void {
    $unpublishedVersion = Version::query()->create([
        'uuid' => (string) Str::uuid(),
        'number' => (Version::query()->max('number') ?? 0) + 1,
        'name' => 'draft-version',
        'is_live' => false,
        'manifest' => [],
        'published_at' => null,
    ]);

    (new Rollback)->rollbackTo($unpublishedVersion);
})->throws(LogicException::class);

it('dispatches VersionRolledBack on success', function (): void {
    Event::fake([VersionRolledBack::class]);

    $firstVersion = publishWorkspaceWithFixture((string) Str::uuid(), 'first');
    publishWorkspaceWithFixture((string) Str::uuid(), 'second');

    (new Rollback)->rollbackTo($firstVersion, reason: 'revert');

    Event::assertDispatched(
        VersionRolledBack::class,
        fn (VersionRolledBack $event): bool => $event->rolledBackTo->is($firstVersion)
            && $event->reason === 'revert',
    );
});

it('records a rollback audit row pointing at the target version', function (): void {
    $firstVersion = publishWorkspaceWithFixture((string) Str::uuid(), 'first');
    publishWorkspaceWithFixture((string) Str::uuid(), 'second');

    $rollbackRecord = (new Rollback)->rollbackTo($firstVersion);

    $fromDb = Version::query()->whereKey($rollbackRecord->id)->first();

    expect($fromDb)->not->toBeNull()
        ->and($fromDb->rollback_of_version_id)->toBe($firstVersion->id)
        ->and($fromDb->is_live)->toBeFalse()
        ->and($fromDb->published_at)->not->toBeNull();
});

it('is transactional — a failing listener leaves the database untouched', function (): void {
    $firstVersion = publishWorkspaceWithFixture((string) Str::uuid(), 'first');
    $secondVersion = publishWorkspaceWithFixture((string) Str::uuid(), 'second');

    $secondVersionLiveBefore = $secondVersion->fresh()->is_live;
    $liveRowCountBefore = WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->where('workspace_id', 0)
        ->count();

    // The service uses DB::transaction; force a failure INSIDE the
    // transaction by registering a database-event listener that throws
    // when the new rollback Version row is being created.
    $listener = static function (object $event): void {
        $sql = property_exists($event, 'sql') ? (string) $event->sql : '';

        throw_if(str_contains($sql, 'insert into "versions"'), RuntimeException::class, 'boom from listener');
    };

    DB::listen($listener);

    try {
        (new Rollback)->rollbackTo($firstVersion);
        expect(true)->toBeFalse('expected rollback to throw');
    } catch (RuntimeException $runtimeException) {
        expect($runtimeException->getMessage())->toContain('boom');
    }

    expect(Version::liveId())->toBe($secondVersion->id)
        ->and($secondVersion->fresh()->is_live)->toBe($secondVersionLiveBefore)
        ->and(WorkspaceDraftableFixture::query()
            ->withoutGlobalScopes()
            ->where('workspace_id', 0)
            ->count())->toBe($liveRowCountBefore);
});
