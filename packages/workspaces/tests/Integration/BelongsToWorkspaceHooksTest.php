<?php

declare(strict_types=1);

use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Tests\Integration\Fixtures\HardDeletableDraftableFixture;
use Capell\Workspaces\Tests\Integration\Fixtures\ShadowableDraftableFixture;
use Capell\Workspaces\WorkspaceContext;
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

function makeLiveRow(string $name = 'live'): ShadowableDraftableFixture
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

// ----- creating hook ---------------------------------------------------------

it('creating: stamps active workspace id onto a new record', function (): void {
    $workspace = Workspace::factory()->create();
    WorkspaceContext::set($workspace);

    $record = ShadowableDraftableFixture::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => 'brand-new',
    ]);

    expect($record->workspace_id)->toBe($workspace->id);
});

it('creating: leaves new records on live when no workspace is active', function (): void {
    $record = ShadowableDraftableFixture::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => 'live-record',
    ]);

    $stored = ShadowableDraftableFixture::query()
        ->withoutGlobalScopes()
        ->find($record->id);

    expect($stored->workspace_id)->toBe(0);
});

it('creating: does not overwrite an explicit non-zero workspace_id', function (): void {
    $workspace = Workspace::factory()->create();
    $otherWorkspace = Workspace::factory()->create();
    WorkspaceContext::set($workspace);

    $record = ShadowableDraftableFixture::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => 'preset',
        'workspace_id' => $otherWorkspace->id,
    ]);

    expect($record->workspace_id)->toBe($otherWorkspace->id);
});

// ----- saving hook -----------------------------------------------------------

it('saving: dirty live row is copied on write and live row is untouched', function (): void {
    $workspace = Workspace::factory()->create();
    $liveRow = makeLiveRow('original');

    WorkspaceContext::set($workspace);

    $liveRow->name = 'edited-in-memory';
    $result = $liveRow->save();

    expect($result)->toBeFalse();

    $storedLive = ShadowableDraftableFixture::query()
        ->withoutGlobalScopes()
        ->find($liveRow->id);

    expect($storedLive->name)->toBe('original')
        ->and($storedLive->workspace_id)->toBe(0)
        ->and($storedLive->shadowed_by_workspace_id)->toBe($workspace->id);

    $clone = ShadowableDraftableFixture::query()
        ->withoutGlobalScopes()
        ->where('workspace_id', $workspace->id)
        ->first();

    expect($clone)->not->toBeNull()
        ->and($clone->name)->toBe('edited-in-memory')
        ->and($clone->uuid)->toBe($liveRow->uuid);
});

it('saving: live row saves normally when no workspace is active', function (): void {
    $liveRow = makeLiveRow('original');

    $liveRow->name = 'renamed';

    $result = $liveRow->save();

    expect($result)->toBeTrue();

    $stored = ShadowableDraftableFixture::query()
        ->withoutGlobalScopes()
        ->find($liveRow->id);

    expect($stored->name)->toBe('renamed')
        ->and($stored->workspace_id)->toBe(0);
});

it('saving: workspace-scoped row saves in place without cloning', function (): void {
    $workspace = Workspace::factory()->create();
    WorkspaceContext::set($workspace);

    $workspaceRow = ShadowableDraftableFixture::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => 'in-workspace',
    ]);

    $workspaceRow->name = 'renamed-in-workspace';

    $result = $workspaceRow->save();

    expect($result)->toBeTrue();

    $count = ShadowableDraftableFixture::query()
        ->withoutGlobalScopes()
        ->where('workspace_id', $workspace->id)
        ->count();

    expect($count)->toBe(1);

    $stored = ShadowableDraftableFixture::query()
        ->withoutGlobalScopes()
        ->find($workspaceRow->id);

    expect($stored->name)->toBe('renamed-in-workspace');
});

it('saving: a clean live row is not copied when saved inside a workspace', function (): void {
    $workspace = Workspace::factory()->create();
    $liveRow = makeLiveRow('pristine');

    WorkspaceContext::set($workspace);

    // No attribute changes — the save should short-circuit before COW.
    $liveRow->save();

    $clones = ShadowableDraftableFixture::query()
        ->withoutGlobalScopes()
        ->where('workspace_id', $workspace->id)
        ->count();

    expect($clones)->toBe(0);
});

// ----- deleting hook ---------------------------------------------------------

it('deleting: live row is turned into a workspace tombstone and not removed', function (): void {
    $workspace = Workspace::factory()->create();
    $liveRow = makeLiveRow('to-delete');

    WorkspaceContext::set($workspace);

    $result = $liveRow->delete();

    expect($result)->toBeFalse();

    $storedLive = ShadowableDraftableFixture::query()
        ->withoutGlobalScopes()
        ->find($liveRow->id);

    expect($storedLive)->not->toBeNull()
        ->and($storedLive->deleted_at)->toBeNull()
        ->and($storedLive->shadowed_by_workspace_id)->toBe($workspace->id);

    $tombstone = ShadowableDraftableFixture::query()
        ->withoutGlobalScopes()
        ->withTrashed()
        ->where('workspace_id', $workspace->id)
        ->first();

    expect($tombstone)->not->toBeNull()
        ->and($tombstone->deleted_at)->not->toBeNull()
        ->and($tombstone->uuid)->toBe($liveRow->uuid);
});

it('deleting: live row is removed normally when no workspace is active', function (): void {
    $liveRow = makeLiveRow();

    $result = $liveRow->delete();

    expect($result)->toBeTrue();

    $stored = ShadowableDraftableFixture::query()
        ->withoutGlobalScopes()
        ->withTrashed()
        ->find($liveRow->id);

    expect($stored->deleted_at)->not->toBeNull();
});

it('deleting: workspace-scoped row is removed normally even with a workspace active', function (): void {
    $workspace = Workspace::factory()->create();
    WorkspaceContext::set($workspace);

    $workspaceRow = ShadowableDraftableFixture::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => 'drafty',
    ]);

    $result = $workspaceRow->delete();

    expect($result)->toBeTrue();

    $stored = ShadowableDraftableFixture::query()
        ->withoutGlobalScopes()
        ->withTrashed()
        ->find($workspaceRow->id);

    expect($stored->deleted_at)->not->toBeNull();
});

it('deleting: hard-deletable live row cannot be tombstoned and the action throws', function (): void {
    $workspace = Workspace::factory()->create();
    $liveRow = HardDeletableDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => 0,
            'shadowed_by_workspace_id' => 0,
            'uuid' => (string) Str::uuid(),
            'name' => 'no-softdeletes',
        ]);

    WorkspaceContext::set($workspace);

    expect(fn () => $liveRow->delete())->toThrow(LogicException::class);
});
