<?php

declare(strict_types=1);

use Capell\PublishingStudio\Actions\BuildReleaseWorkspaceReadinessAction;
use Capell\PublishingStudio\Enums\WorkspaceKindEnum;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Models\Version;
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

it('marks an approved release workspace ready when dry run would publish', function (): void {
    $workspace = Workspace::factory()->create([
        'kind' => WorkspaceKindEnum::Release,
        'status' => WorkspaceStatusEnum::Approved,
        'base_version_id' => Version::liveId(),
    ]);

    WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => $workspace->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'Launch draft',
        ]);

    $readiness = BuildReleaseWorkspaceReadinessAction::run($workspace);

    expect($readiness->workspaceId)->toBe($workspace->id)
        ->and($readiness->wouldPublish)->toBeTrue()
        ->and($readiness->blockingIssueCount)->toBe(0);
});

it('reports embargoed release workspaces as blocked', function (): void {
    $workspace = Workspace::factory()->create([
        'kind' => WorkspaceKindEnum::Release,
        'status' => WorkspaceStatusEnum::Approved,
        'embargo_until' => now()->addDay(),
    ]);

    WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => $workspace->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'Embargoed launch draft',
        ]);

    $readiness = BuildReleaseWorkspaceReadinessAction::run($workspace);

    expect($readiness->wouldPublish)->toBeFalse()
        ->and($readiness->blockingIssueCount)->toBeGreaterThan(0)
        ->and($readiness->blockingIssues[0])->toContain('embargo');
});
