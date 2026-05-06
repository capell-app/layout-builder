<?php

declare(strict_types=1);

use Capell\PublishingStudio\Actions\InstallWorkspaceRolesAction;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions\ApproveAction;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions\PublishAction;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions\SaveAsDraftAction;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions\SubmitForApprovalAction;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Pages\ManagePublishingStudio;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Tests\Integration\Fixtures\WorkspaceDraftableFixture;
use Capell\PublishingStudio\WorkspaceRegistry;
use Capell\Tests\Fixtures\Models\User;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use function Pest\Livewire\livewire;

use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class)
    ->group('publishing-studio');

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

    foreach ([
        'ViewAny:Workspace',
        'View:Workspace',
        'Create:Workspace',
        'Update:Workspace',
        'Delete:Workspace',
        'submit_workspace_for_approval',
        'approve_workspace',
        'publish_workspace',
        'rollback_workspace',
    ] as $permissionName) {
        Permission::findOrCreate($permissionName, 'web');
    }

    InstallWorkspaceRolesAction::run();

    Route::get('/fake-frontend', fn (): string => 'ok')->name('capell-frontend.index');
    Route::get('/fake-frontend-page/{page?}', fn (): string => 'ok')->name('capell-frontend.page');
});

afterEach(function (): void {
    Schema::dropIfExists('workspace_draftable_fixtures');
    WorkspaceRegistry::reset();
});

it('keeps workspace in Open state when saved as draft', function (): void {
    $workspace = Workspace::factory()->open()->create([
        'name' => 'Draft Workspace',
    ]);

    $editor = User::factory()->create();
    $editor->assignRole(InstallWorkspaceRolesAction::ROLE_EDITOR);
    $editor->givePermissionTo(['ViewAny:Workspace', 'View:Workspace', 'Update:Workspace']);

    test()->actingAs($editor);

    livewire(ManagePublishingStudio::class)
        ->callAction(TestAction::make(SaveAsDraftAction::class)->table($workspace))
        ->assertHasNoActionErrors()
        ->assertNotified(__('capell-admin::workspace.notifications.saved_as_draft'));

    expect($workspace->refresh()->status)->toBe(WorkspaceStatusEnum::Open);
});

it('does not show Save as Draft on a workspace that is in review', function (): void {
    $workspace = Workspace::factory()->inReview()->create();

    $editor = User::factory()->create();
    $editor->assignRole(InstallWorkspaceRolesAction::ROLE_EDITOR);
    $editor->givePermissionTo(['ViewAny:Workspace', 'View:Workspace', 'Update:Workspace']);

    test()->actingAs($editor);

    livewire(ManagePublishingStudio::class)
        ->assertActionHidden(TestAction::make(SaveAsDraftAction::class)->table($workspace));
});

it('supports the full workflow: save as draft → submit → approve → publish', function (): void {
    $matchingUuid = (string) Str::uuid();

    WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => 0,
            'uuid' => $matchingUuid,
            'name' => 'live-original',
        ]);

    $workspace = Workspace::factory()->open()->create([
        'name' => 'End-to-end Release',
        'settings' => ['requiredApprovalLevels' => 1],
    ]);

    WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => $workspace->id,
            'uuid' => $matchingUuid,
            'name' => 'workspace-edited',
        ]);

    $editor = User::factory()->create();
    $editor->assignRole(InstallWorkspaceRolesAction::ROLE_EDITOR);
    $editor->givePermissionTo(['ViewAny:Workspace', 'View:Workspace', 'Update:Workspace']);

    test()->actingAs($editor);

    // Step 1: Save as Draft — workspace stays Open
    livewire(ManagePublishingStudio::class)
        ->callAction(TestAction::make(SaveAsDraftAction::class)->table($workspace))
        ->assertHasNoActionErrors()
        ->assertNotified(__('capell-admin::workspace.notifications.saved_as_draft'));

    $workspace->refresh();
    expect($workspace->status)->toBe(WorkspaceStatusEnum::Open);

    // Step 2: Submit for approval — Open → InReview
    livewire(ManagePublishingStudio::class)
        ->callAction(TestAction::make(SubmitForApprovalAction::class)->table($workspace))
        ->assertHasNoActionErrors();

    $workspace->refresh();
    expect($workspace->status)->toBe(WorkspaceStatusEnum::InReview);

    // Step 3: Approve — InReview → Approved
    $reviewer = User::factory()->create();
    $reviewer->assignRole(InstallWorkspaceRolesAction::ROLE_REVIEWER);
    $reviewer->givePermissionTo(['ViewAny:Workspace', 'View:Workspace']);

    test()->actingAs($reviewer);

    livewire(ManagePublishingStudio::class)
        ->callAction(
            TestAction::make(ApproveAction::class)->table($workspace),
            data: ['notes' => 'Approved after draft review'],
        )
        ->assertHasNoActionErrors();

    $workspace->refresh();
    expect($workspace->status)->toBe(WorkspaceStatusEnum::Approved);

    // Step 4: Publish — Approved → Published
    $releaseManager = User::factory()->create();
    $releaseManager->assignRole(InstallWorkspaceRolesAction::ROLE_RELEASE_MANAGER);
    $releaseManager->givePermissionTo(['ViewAny:Workspace', 'View:Workspace']);

    test()->actingAs($releaseManager);

    livewire(ManagePublishingStudio::class)
        ->callAction(TestAction::make(PublishAction::class)->table($workspace))
        ->assertHasNoActionErrors();

    $workspace->refresh();
    expect($workspace->status)->toBe(WorkspaceStatusEnum::Published)
        ->and($workspace->published_at)->not->toBeNull();

    $flippedName = WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->where('workspace_id', 0)
        ->where('uuid', $matchingUuid)
        ->value('name');

    expect($flippedName)->toBe('workspace-edited');
});
