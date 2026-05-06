<?php

declare(strict_types=1);

use Capell\PublishingStudio\CloneOptions;
use Capell\PublishingStudio\CloneWorkspaceAction;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Models\Workspace;
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

it('clones a workspace with all draftable rows under the new workspace id', function (): void {
    $source = Workspace::factory()->create();

    $sourceRowCount = 3;
    for ($rowIndex = 0; $rowIndex < $sourceRowCount; $rowIndex++) {
        WorkspaceDraftableFixture::query()->withoutGlobalScopes()->create([
            'workspace_id' => $source->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'row-' . $rowIndex,
        ]);
    }

    $clone = (new CloneWorkspaceAction)->clone($source, new CloneOptions(newName: 'Campaign Q3'));

    expect($clone->id)->not->toBe($source->id)
        ->and($clone->name)->toBe('Campaign Q3')
        ->and($clone->cloned_from_id)->toBe($source->id)
        ->and($clone->status)->toBe(WorkspaceStatusEnum::Open)
        ->and(WorkspaceDraftableFixture::query()->withoutGlobalScopes()->where('workspace_id', $clone->id)->count())->toBe($sourceRowCount);
});

it('skips draft copy when copyDrafts is false', function (): void {
    $source = Workspace::factory()->create();
    WorkspaceDraftableFixture::query()->withoutGlobalScopes()->create([
        'workspace_id' => $source->id,
        'uuid' => (string) Str::uuid(),
        'name' => 'only',
    ]);

    $clone = (new CloneWorkspaceAction)->clone($source, new CloneOptions(copyDrafts: false));

    expect(WorkspaceDraftableFixture::query()->withoutGlobalScopes()->where('workspace_id', $clone->id)->count())->toBe(0);
});

it('leaves source rows untouched', function (): void {
    $source = Workspace::factory()->create();
    WorkspaceDraftableFixture::query()->withoutGlobalScopes()->create([
        'workspace_id' => $source->id,
        'uuid' => (string) Str::uuid(),
        'name' => 'original',
    ]);

    (new CloneWorkspaceAction)->clone($source);

    expect(WorkspaceDraftableFixture::query()->withoutGlobalScopes()->where('workspace_id', $source->id)->count())->toBe(1);
});
