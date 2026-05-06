<?php

declare(strict_types=1);

use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Rebaser;
use Capell\PublishingStudio\Tests\Integration\Fixtures\WorkspaceDraftableFixture;
use Capell\PublishingStudio\WorkspaceRegistry;
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

    WorkspaceRegistry::reset();
    WorkspaceRegistry::register(WorkspaceDraftableFixture::class);
});

afterEach(function (): void {
    Schema::dropIfExists('workspace_draftable_fixtures');
    WorkspaceRegistry::reset();
});

it('take-live deletes the workspace copy so live shines through', function (): void {
    $workspace = Workspace::factory()->create();
    $uuid = (string) Str::uuid();

    WorkspaceDraftableFixture::query()->withoutGlobalScopes()->create([
        'workspace_id' => 0,
        'uuid' => $uuid,
        'name' => 'live',
    ]);
    WorkspaceDraftableFixture::query()->withoutGlobalScopes()->create([
        'workspace_id' => $workspace->id,
        'uuid' => $uuid,
        'name' => 'my changes',
    ]);

    (new Rebaser)->resolve($workspace, [
        WorkspaceDraftableFixture::class => [$uuid => 'take-live'],
    ]);

    $remaining = WorkspaceDraftableFixture::query()->withoutGlobalScopes()
        ->where('uuid', $uuid)
        ->where('workspace_id', $workspace->id)
        ->count();

    expect($remaining)->toBe(0);
});

it('keep-mine leaves the workspace copy untouched', function (): void {
    $workspace = Workspace::factory()->create();
    $uuid = (string) Str::uuid();

    WorkspaceDraftableFixture::query()->withoutGlobalScopes()->create([
        'workspace_id' => $workspace->id,
        'uuid' => $uuid,
        'name' => 'my changes',
    ]);

    (new Rebaser)->resolve($workspace, [
        WorkspaceDraftableFixture::class => [$uuid => 'keep-mine'],
    ]);

    $workspaceRow = WorkspaceDraftableFixture::query()->withoutGlobalScopes()
        ->where('uuid', $uuid)
        ->where('workspace_id', $workspace->id)
        ->first();

    expect($workspaceRow)->not->toBeNull()
        ->and($workspaceRow->name)->toBe('my changes');
});

it('throws on an unknown choice value rather than silently no-opping', function (): void {
    $workspace = Workspace::factory()->create();
    $uuid = (string) Str::uuid();

    expect(fn (): mixed => (new Rebaser)->resolve($workspace, [
        WorkspaceDraftableFixture::class => [$uuid => 'merge-somehow'],
    ]))->toThrow(InvalidArgumentException::class);
});
