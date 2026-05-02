<?php

declare(strict_types=1);

use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Tests\Integration\Fixtures\WorkspaceDraftableFixture;
use Capell\Workspaces\WorkspaceRegistry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
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

it('prunes abandoned workspaces, clears shadowed live rows, and deletes draft rows', function (): void {
    $abandonedWorkspace = Workspace::factory()->abandoned()->create();
    $openWorkspace = Workspace::factory()->open()->create();

    $liveUuid = (string) Str::uuid();
    $liveRow = new WorkspaceDraftableFixture;
    $liveRow->forceFill([
        'workspace_id' => 0,
        'shadowed_by_workspace_id' => $abandonedWorkspace->id,
        'uuid' => $liveUuid,
        'name' => 'live-original',
    ])->save();

    WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => $abandonedWorkspace->id,
            'uuid' => $liveUuid,
            'name' => 'draft-edit',
        ]);

    WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => $openWorkspace->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'untouched-draft',
        ]);

    $exitCode = Artisan::call('capell:workspaces:prune');

    $liveRow->refresh();

    expect($exitCode)->toBe(0)
        ->and((int) $liveRow->getAttribute('shadowed_by_workspace_id'))->toBe(0)
        ->and(Workspace::query()->withTrashed()->whereKey($abandonedWorkspace->id)->exists())->toBeFalse()
        ->and(WorkspaceDraftableFixture::query()
            ->withoutGlobalScopes()
            ->where('workspace_id', $abandonedWorkspace->id)
            ->count())->toBe(0)
        ->and(WorkspaceDraftableFixture::query()
            ->withoutGlobalScopes()
            ->where('workspace_id', $openWorkspace->id)
            ->count())->toBe(1);
});

it('dry-run leaves data untouched', function (): void {
    $workspace = Workspace::factory()->abandoned()->create();

    WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => $workspace->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'draft-intact',
        ]);

    $exitCode = Artisan::call('capell:workspaces:prune', ['--dry-run' => true]);

    expect($exitCode)->toBe(0)
        ->and(Workspace::query()->whereKey($workspace->id)->exists())->toBeTrue()
        ->and(WorkspaceDraftableFixture::query()
            ->withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->count())->toBe(1);
});

it('can prune a specific workspace id regardless of status', function (): void {
    $openWorkspace = Workspace::factory()->open()->create();

    WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => $openWorkspace->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'draft-to-drop',
        ]);

    $exitCode = Artisan::call('capell:workspaces:prune', ['--id' => [$openWorkspace->id]]);

    expect($exitCode)->toBe(0)
        ->and(Workspace::query()->withTrashed()->whereKey($openWorkspace->id)->exists())->toBeFalse();
});
