<?php

declare(strict_types=1);

use Capell\Workspaces\Actions\CopyOnWriteAction;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Tests\Integration\Fixtures\HardDeletableDraftableFixture;
use Capell\Workspaces\Tests\Integration\Fixtures\ShadowableDraftableFixture;
use Capell\Workspaces\WorkspaceContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Schema::create('shadowable_draftable_fixtures', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('workspace_id')->default(0)->index();
        $table->unsignedBigInteger('shadowed_by_workspace_id')->default(0)->index();
        $table->uuid('uuid');
        $table->string('name');
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('hard_deletable_draftable_fixtures', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('workspace_id')->default(0)->index();
        $table->unsignedBigInteger('shadowed_by_workspace_id')->default(0)->index();
        $table->uuid('uuid');
        $table->string('name');
        $table->timestamps();
    });

    WorkspaceContext::clear();
});

afterEach(function (): void {
    Schema::dropIfExists('shadowable_draftable_fixtures');
    Schema::dropIfExists('hard_deletable_draftable_fixtures');
    WorkspaceContext::clear();
});

function makeLiveShadowableRow(string $name = 'live'): ShadowableDraftableFixture
{
    return ShadowableDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => 0,
            'shadowed_by_workspace_id' => 0,
            'uuid' => (string) Str::uuid(),
            'name' => $name,
        ]);
}

function makeLiveHardDeletableRow(string $name = 'live'): HardDeletableDraftableFixture
{
    return HardDeletableDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => 0,
            'shadowed_by_workspace_id' => 0,
            'uuid' => (string) Str::uuid(),
            'name' => $name,
        ]);
}

it('cloneForEdit leaves the live row on disk untouched', function (): void {
    $workspace = Workspace::factory()->create();
    $liveRow = makeLiveShadowableRow('live-name');
    $originalId = $liveRow->id;
    $originalUuid = $liveRow->uuid;

    $liveRow->name = 'edited-in-memory';

    (new CopyOnWriteAction)->cloneForEdit($liveRow, $workspace);

    $stored = ShadowableDraftableFixture::query()
        ->withoutGlobalScopes()
        ->find($originalId);

    expect($stored)->not->toBeNull()
        ->and($stored->name)->toBe('live-name')
        ->and($stored->workspace_id)->toBe(0)
        ->and($stored->uuid)->toBe($originalUuid);
});

it('cloneForEdit persists a workspace-scoped clone with dirty attributes applied', function (): void {
    $workspace = Workspace::factory()->create();
    $liveRow = makeLiveShadowableRow('original-name');

    $liveRow->name = 'edited-name';

    $clone = (new CopyOnWriteAction)->cloneForEdit($liveRow, $workspace);

    expect($clone->exists)->toBeTrue()
        ->and($clone->id)->not->toBe($liveRow->id)
        ->and($clone->workspace_id)->toBe($workspace->id)
        ->and($clone->name)->toBe('edited-name')
        ->and($clone->shadowed_by_workspace_id)->toBe(0);
});

it('cloneForEdit preserves the uuid of the source live row on the clone', function (): void {
    $workspace = Workspace::factory()->create();
    $liveRow = makeLiveShadowableRow();

    $liveRow->name = 'renamed';

    $clone = (new CopyOnWriteAction)->cloneForEdit($liveRow, $workspace);

    expect($clone->uuid)->toBe($liveRow->uuid);
});

it('cloneForEdit stamps the shadow flag on the live row', function (): void {
    $workspace = Workspace::factory()->create();
    $liveRow = makeLiveShadowableRow();
    $liveRow->name = 'edited';

    (new CopyOnWriteAction)->cloneForEdit($liveRow, $workspace);

    $liveAfter = ShadowableDraftableFixture::query()
        ->withoutGlobalScopes()
        ->find($liveRow->id);

    expect($liveAfter->shadowed_by_workspace_id)->toBe($workspace->id)
        ->and($liveRow->shadowed_by_workspace_id)->toBe($workspace->id);
});

it('cloneForDelete persists the clone and soft-deletes it to mark a tombstone', function (): void {
    $workspace = Workspace::factory()->create();
    $liveRow = makeLiveShadowableRow();

    $clone = (new CopyOnWriteAction)->cloneForDelete($liveRow, $workspace);

    expect($clone->exists)->toBeTrue()
        ->and($clone->workspace_id)->toBe($workspace->id)
        ->and($clone->trashed())->toBeTrue()
        ->and($clone->uuid)->toBe($liveRow->uuid);

    $storedLive = ShadowableDraftableFixture::query()
        ->withoutGlobalScopes()
        ->find($liveRow->id);

    expect($storedLive->trashed())->toBeFalse()
        ->and($storedLive->shadowed_by_workspace_id)->toBe($workspace->id);
});

it('cloneForDelete throws a LogicException for models that do not use SoftDeletes', function (): void {
    $workspace = Workspace::factory()->create();
    $liveRow = makeLiveHardDeletableRow();

    expect(fn (): Model => (new CopyOnWriteAction)->cloneForDelete($liveRow, $workspace))
        ->toThrow(LogicException::class, 'does not use SoftDeletes');
});

it('clearShadow only clears the flag when the workspace id matches', function (): void {
    $ownerWorkspace = Workspace::factory()->create();
    $otherWorkspace = Workspace::factory()->create();

    $liveRow = makeLiveShadowableRow();
    $liveRow->name = 'edited';
    (new CopyOnWriteAction)->cloneForEdit($liveRow, $ownerWorkspace);

    // A mismatched workspace must not reset the shadow flag.
    (new CopyOnWriteAction)->clearShadow($liveRow, $otherWorkspace);

    $stillShadowed = ShadowableDraftableFixture::query()
        ->withoutGlobalScopes()
        ->find($liveRow->id);

    expect($stillShadowed->shadowed_by_workspace_id)->toBe($ownerWorkspace->id);

    // The matching workspace resets it.
    (new CopyOnWriteAction)->clearShadow($liveRow, $ownerWorkspace);

    $cleared = ShadowableDraftableFixture::query()
        ->withoutGlobalScopes()
        ->find($liveRow->id);

    expect($cleared->shadowed_by_workspace_id)->toBe(0);
});

it('guardLive rejects records that are not live rows on cloneForEdit', function (): void {
    $workspace = Workspace::factory()->create();

    $alreadyScopedRow = ShadowableDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => $workspace->id,
            'shadowed_by_workspace_id' => 0,
            'uuid' => (string) Str::uuid(),
            'name' => 'draft-only',
        ]);

    expect(fn (): Model => (new CopyOnWriteAction)->cloneForEdit($alreadyScopedRow, $workspace))
        ->toThrow(LogicException::class, 'expected a live row');
});

it('guardLive rejects records that are not live rows on cloneForDelete', function (): void {
    $workspace = Workspace::factory()->create();

    $alreadyScopedRow = ShadowableDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => $workspace->id,
            'shadowed_by_workspace_id' => 0,
            'uuid' => (string) Str::uuid(),
            'name' => 'draft-only',
        ]);

    expect(fn (): Model => (new CopyOnWriteAction)->cloneForDelete($alreadyScopedRow, $workspace))
        ->toThrow(LogicException::class, 'expected a live row');
});
