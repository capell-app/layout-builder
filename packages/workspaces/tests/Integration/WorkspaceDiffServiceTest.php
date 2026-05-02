<?php

declare(strict_types=1);

use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Services\WorkspaceDiffService;
use Capell\Workspaces\Tests\Integration\Fixtures\WorkspaceDraftableFixture;
use Capell\Workspaces\WorkspaceRegistry;
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

it('returns a tree tagging changed and unchanged attributes', function (): void {
    $workspace = Workspace::factory()->create();
    $uuid = (string) Str::uuid();

    WorkspaceDraftableFixture::query()->withoutGlobalScopes()->create([
        'workspace_id' => 0,
        'uuid' => $uuid,
        'name' => 'original',
    ]);
    WorkspaceDraftableFixture::query()->withoutGlobalScopes()->create([
        'workspace_id' => $workspace->id,
        'uuid' => $uuid,
        'name' => 'updated',
    ]);

    $tree = (new WorkspaceDiffService)->diffTree($workspace);

    expect($tree)->toHaveCount(1);

    $entry = $tree->first();
    expect($entry['kind'])->toBe('modified')
        ->and($entry['attributes']['name'])->toMatchArray([
            'status' => 'changed',
            'before' => 'original',
            'after' => 'updated',
        ])
        ->and($entry['attributes']['uuid'])->toMatchArray([
            'status' => 'unchanged',
            'value' => $uuid,
        ]);
});

it('marks rows with no live counterpart as added', function (): void {
    $workspace = Workspace::factory()->create();

    WorkspaceDraftableFixture::query()->withoutGlobalScopes()->create([
        'workspace_id' => $workspace->id,
        'uuid' => (string) Str::uuid(),
        'name' => 'brand new',
    ]);

    $tree = (new WorkspaceDiffService)->diffTree($workspace);

    expect($tree->first()['kind'])->toBe('added')
        ->and($tree->first()['attributes']['name']['status'])->toBe('added');
});
