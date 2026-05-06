<?php

declare(strict_types=1);

use Capell\PublishingStudio\Models\Version;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Publisher;
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

it('dry-run of an approved workspace dashboard-dashboard_reports what would publish and rolls back', function (): void {
    $workspace = Workspace::factory()->approved()->create();
    $uuid = (string) Str::uuid();

    WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => $workspace->id,
            'uuid' => $uuid,
            'name' => 'would-publish',
        ]);

    $latestVersionId = (int) (Version::query()->max('id') ?? 0);

    $report = (new Publisher)->dryRun($workspace);

    expect($report->wouldPublish)->toBeTrue()
        ->and($report->failure)->toBeNull()
        ->and($report->totalRows())->toBe(1)
        ->and($report->rowCounts)->toHaveKey(WorkspaceDraftableFixture::class)
        ->and((int) (Version::query()->max('id') ?? 0))->toBe($latestVersionId)
        ->and(WorkspaceDraftableFixture::query()
            ->withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->count())->toBe(1);

    $workspace->refresh();
    expect($workspace->status->value)->toBe('approved');
});

it('dry-run of a non-approved workspace captures the failure and does not run', function (): void {
    $workspace = Workspace::factory()->open()->create();

    $report = (new Publisher)->dryRun($workspace);

    expect($report->wouldPublish)->toBeFalse()
        ->and($report->failure)->not->toBeNull();
});
