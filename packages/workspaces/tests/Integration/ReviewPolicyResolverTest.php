<?php

declare(strict_types=1);

use Capell\Workspaces\Approvals\ReviewPolicyResolver;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Tests\Integration\Fixtures\WorkspaceDraftableFixture;
use Capell\Workspaces\WorkspaceRegistry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
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

it('falls back to the default minimum reviewers when no content-type rule matches', function (): void {
    Config::set('capell.workspaces.review_policy', [
        'default' => ['minimum' => 2],
        'content_types' => [],
    ]);

    $workspace = Workspace::factory()->create();

    $required = (new ReviewPolicyResolver)->resolve($workspace);

    expect($required)->toHaveCount(2)
        ->and($required->first()->requiredFor)->toBe('any');
});

it('emits role-scoped rules for each content type present in the workspace', function (): void {
    Config::set('capell.workspaces.review_policy', [
        'default' => ['minimum' => 1],
        'content_types' => [
            WorkspaceDraftableFixture::class => [
                'required_roles' => ['content-editor', 'legal'],
            ],
        ],
    ]);

    $workspace = Workspace::factory()->create();
    WorkspaceDraftableFixture::query()->withoutGlobalScopes()->create([
        'workspace_id' => $workspace->id,
        'uuid' => (string) Str::uuid(),
        'name' => 'hello',
    ]);

    $required = (new ReviewPolicyResolver)->resolve($workspace);

    expect($required)->toHaveCount(2)
        ->and($required->pluck('role')->all())->toBe(['content-editor', 'legal']);
});
