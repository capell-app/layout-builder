<?php

declare(strict_types=1);

use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Tests\Integration\Fixtures\WorkspaceDraftableFixture;
use Capell\PublishingStudio\WorkspaceContext;
use Illuminate\Database\Eloquent\Model;
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

    WorkspaceContext::clear();
});

afterEach(function (): void {
    Schema::dropIfExists('workspace_draftable_fixtures');
    WorkspaceContext::clear();
});

function makeDraftableRow(int $workspaceId, string $name): WorkspaceDraftableFixture
{
    return WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => $workspaceId,
            'uuid' => (string) Str::uuid(),
            'name' => $name,
        ]);
}

it('filters to live rows by default when no workspace is active', function (): void {
    makeDraftableRow(0, 'live-only');
    makeDraftableRow(123, 'workspace-copy');

    $rows = WorkspaceDraftableFixture::query()->pluck('name')->all();

    expect($rows)->toBe(['live-only']);
});

it('skips workspace filtering before workspace columns are migrated', function (): void {
    Schema::dropIfExists('workspace_draftable_fixtures');

    Schema::create('workspace_draftable_fixtures', function (Blueprint $table): void {
        $table->id();
        $table->uuid('uuid');
        $table->string('name');
        $table->timestamps();
    });

    Model::clearBootedModels();

    WorkspaceDraftableFixture::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => 'pending-migration',
    ]);

    $workspace = new Workspace;
    $workspace->forceFill(['id' => 123]);
    WorkspaceContext::set($workspace);

    $rows = WorkspaceDraftableFixture::query()->pluck('name')->all();

    expect($rows)->toBe(['pending-migration']);
});

it('unions live and active workspace rows when a workspace is active', function (): void {
    $workspace = Workspace::factory()->create();

    // Seed rows with context cleared so BelongsToWorkspace's `creating` hook
    // doesn't auto-stamp the intended live row with the workspace id.
    makeDraftableRow(0, 'live-only');
    makeDraftableRow($workspace->id, 'workspace-copy');
    makeDraftableRow(999, 'other-workspace');

    WorkspaceContext::set($workspace);

    $names = WorkspaceDraftableFixture::query()->pluck('name')->all();

    expect($names)->toEqualCanonicalizing(['live-only', 'workspace-copy']);
});

it('live scope always matches only the live rows', function (): void {
    $workspace = Workspace::factory()->create();

    // Seed live + workspace rows first, then activate the context. The
    // `creating` hook would otherwise stamp the workspace id onto the live
    // row the moment a workspace is active.
    makeDraftableRow(0, 'live');
    makeDraftableRow($workspace->id, 'draft');

    WorkspaceContext::set($workspace);

    $live = WorkspaceDraftableFixture::query()->live()->pluck('name')->all();

    expect($live)->toBe(['live']);
});

it('inWorkspace scope filters to a specific workspace id', function (): void {
    makeDraftableRow(0, 'live');
    makeDraftableRow(50, 'draft-in-50');
    makeDraftableRow(60, 'draft-in-60');

    $names = WorkspaceDraftableFixture::query()
        ->withoutWorkspaceScope()
        ->inWorkspace(50)
        ->pluck('name')
        ->all();

    expect($names)->toBe(['draft-in-50']);
});

it('forContext returns live + workspace rows when given a workspace', function (): void {
    $workspace = Workspace::factory()->create();

    makeDraftableRow(0, 'live');
    makeDraftableRow($workspace->id, 'in-workspace');
    makeDraftableRow(5000, 'elsewhere');

    $names = WorkspaceDraftableFixture::query()
        ->withoutWorkspaceScope()
        ->forContext($workspace)
        ->pluck('name')
        ->all();

    expect($names)->toEqualCanonicalizing(['live', 'in-workspace']);
});

it('forContext falls back to live only when given null', function (): void {
    makeDraftableRow(0, 'live');
    makeDraftableRow(500, 'draft');

    $names = WorkspaceDraftableFixture::query()
        ->withoutWorkspaceScope()
        ->forContext(null)
        ->pluck('name')
        ->all();

    expect($names)->toBe(['live']);
});

it('isLive and isInWorkspace reflect the workspace_id column value', function (): void {
    $liveRow = makeDraftableRow(0, 'live');
    $draftRow = makeDraftableRow(42, 'draft');

    expect($liveRow->isLive())->toBeTrue()
        ->and($liveRow->isInWorkspace())->toBeFalse()
        ->and($draftRow->isLive())->toBeFalse()
        ->and($draftRow->isInWorkspace())->toBeTrue();
});
