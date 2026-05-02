<?php

declare(strict_types=1);

use Capell\Workspaces\Exceptions\EntityNotInVersionException;
use Capell\Workspaces\Models\Version;
use Capell\Workspaces\Rollback\EntityRollbackAction;
use Capell\Workspaces\Rollback\EntityRollbackReport;
use Capell\Workspaces\Tests\Integration\Fixtures\WorkspaceDraftableFixture;
use Illuminate\Database\Schema\Blueprint;
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
});

afterEach(function (): void {
    Schema::dropIfExists('workspace_draftable_fixtures');
});

function makeVersion(array $manifest): Version
{
    return Version::query()->create([
        'uuid' => (string) Str::uuid(),
        'number' => (int) (Version::query()->max('number') ?? 0) + 1,
        'name' => 'version',
        'is_live' => false,
        'manifest' => $manifest,
        'published_at' => now(),
    ]);
}

it('restores the single entity row referenced by the target version', function (): void {
    $entityUuid = (string) Str::uuid();

    $targetRow = WorkspaceDraftableFixture::query()->withoutGlobalScopes()->create([
        'workspace_id' => 999,
        'uuid' => $entityUuid,
        'name' => 'version-1 original',
    ]);

    $currentLive = WorkspaceDraftableFixture::query()->withoutGlobalScopes()->create([
        'workspace_id' => 0,
        'uuid' => $entityUuid,
        'name' => 'version-3 current',
    ]);

    $target = makeVersion([WorkspaceDraftableFixture::class => [$targetRow->id]]);

    $report = (new EntityRollbackAction)->handle(
        modelClass: WorkspaceDraftableFixture::class,
        entityUuid: $entityUuid,
        targetVersion: $target,
    );

    $restored = WorkspaceDraftableFixture::query()->withoutGlobalScopes()
        ->where('uuid', $entityUuid)
        ->where('workspace_id', 0)
        ->get();

    expect($restored)->toHaveCount(1)
        ->and($restored->first()->name)->toBe('version-1 original')
        ->and($report->restoredId)->toBe($targetRow->id)
        ->and($report->replacedId)->toBe($currentLive->id);
});

it('throws when the entity is not part of the target version manifest', function (): void {
    $target = makeVersion([WorkspaceDraftableFixture::class => []]);

    expect(fn (): EntityRollbackReport => (new EntityRollbackAction)->handle(
        modelClass: WorkspaceDraftableFixture::class,
        entityUuid: 'missing-uuid',
        targetVersion: $target,
    ))->toThrow(EntityNotInVersionException::class);
});

it('is a no-op when the target row is already the sole live row', function (): void {
    $entityUuid = (string) Str::uuid();

    $liveRow = WorkspaceDraftableFixture::query()->withoutGlobalScopes()->create([
        'workspace_id' => 0,
        'uuid' => $entityUuid,
        'name' => 'already-live',
    ]);

    $target = makeVersion([WorkspaceDraftableFixture::class => [$liveRow->id]]);

    $report = (new EntityRollbackAction)->handle(
        modelClass: WorkspaceDraftableFixture::class,
        entityUuid: $entityUuid,
        targetVersion: $target,
    );

    expect($report->noOp)->toBeTrue()
        ->and($report->restoredId)->toBe($liveRow->id);
});
