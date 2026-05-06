<?php

declare(strict_types=1);

use Capell\PublishingStudio\Actions\InstallWorkspaceRolesAction;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Enums\WorkspaceTransitionEnum;
use Capell\PublishingStudio\Events\WorkspaceStateChanged;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions\ApproveAction;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions\PublishAction;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions\SubmitForApprovalAction;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions\ValidateAction;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Pages\ManagePublishingStudio;
use Capell\PublishingStudio\Models\Version;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Tests\Integration\Fixtures\WorkspaceDraftableFixture;
use Capell\PublishingStudio\WorkspaceRegistry;
use Capell\Tests\Fixtures\Models\User;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
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

it('runs the validate → approve → publish flow end to end via the Filament table', function (): void {
    Event::fake([WorkspaceStateChanged::class]);

    $matchingUuid = (string) Str::uuid();

    WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->create([
            'workspace_id' => 0,
            'uuid' => $matchingUuid,
            'name' => 'live-original',
        ]);

    $workspace = Workspace::factory()->open()->create([
        'name' => 'Release April',
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
    $editor->givePermissionTo(['ViewAny:Workspace', 'View:Workspace']);

    test()->actingAs($editor);

    livewire(ManagePublishingStudio::class)
        ->callAction(TestAction::make(SubmitForApprovalAction::class)->table($workspace))
        ->assertHasNoActionErrors();

    $workspace->refresh();
    expect($workspace->status)->toBe(WorkspaceStatusEnum::InReview);

    $reviewer = User::factory()->create();
    $reviewer->assignRole(InstallWorkspaceRolesAction::ROLE_REVIEWER);
    $reviewer->givePermissionTo(['ViewAny:Workspace', 'View:Workspace']);

    test()->actingAs($reviewer);

    livewire(ManagePublishingStudio::class)
        ->callAction(TestAction::make(ValidateAction::class)->table($workspace))
        ->assertHasNoActionErrors()
        ->assertNotified();

    $liveName = WorkspaceDraftableFixture::query()
        ->withoutGlobalScopes()
        ->where('workspace_id', 0)
        ->where('uuid', $matchingUuid)
        ->value('name');

    expect($liveName)->toBe('live-original');
    expect($workspace->fresh()->status)->toBe(WorkspaceStatusEnum::InReview);

    livewire(ManagePublishingStudio::class)
        ->callAction(
            TestAction::make(ApproveAction::class)->table($workspace),
            data: ['notes' => 'Looks good'],
        )
        ->assertHasNoActionErrors();

    $workspace->refresh();
    expect($workspace->status)->toBe(WorkspaceStatusEnum::Approved);

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

    $latestLive = Version::currentLive();
    expect($latestLive)->not->toBeNull()
        ->and($latestLive->source_workspace_id)->toBe($workspace->id);

    Event::assertDispatched(
        WorkspaceStateChanged::class,
        fn (WorkspaceStateChanged $event): bool => $event->workspace->is($workspace)
            && $event->transition === WorkspaceTransitionEnum::Submitted->value,
    );
    Event::assertDispatched(
        WorkspaceStateChanged::class,
        fn (WorkspaceStateChanged $event): bool => $event->workspace->is($workspace)
            && $event->transition === WorkspaceTransitionEnum::Approved->value,
    );
    Event::assertDispatched(
        WorkspaceStateChanged::class,
        fn (WorkspaceStateChanged $event): bool => $event->workspace->is($workspace)
            && $event->transition === WorkspaceTransitionEnum::Published->value,
    );
});

it('records the next approval level instead of approving all levels at once', function (): void {
    $workspace = Workspace::factory()->inReview()->create([
        'settings' => ['requiredApprovalLevels' => 2],
    ]);

    $firstReviewer = User::factory()->create();
    $firstReviewer->assignRole(InstallWorkspaceRolesAction::ROLE_REVIEWER);
    $firstReviewer->givePermissionTo(['ViewAny:Workspace', 'View:Workspace']);

    test()->actingAs($firstReviewer);

    livewire(ManagePublishingStudio::class)
        ->callAction(
            TestAction::make(ApproveAction::class)->table($workspace),
            data: ['notes' => 'First approval'],
        )
        ->assertHasNoActionErrors();

    $workspace->refresh();

    expect($workspace->status)->toBe(WorkspaceStatusEnum::InReview)
        ->and($workspace->approvals()->where('action', 'approved')->value('level'))->toBe(1);

    $secondReviewer = User::factory()->create();
    $secondReviewer->assignRole(InstallWorkspaceRolesAction::ROLE_REVIEWER);
    $secondReviewer->givePermissionTo(['ViewAny:Workspace', 'View:Workspace']);

    test()->actingAs($secondReviewer);

    livewire(ManagePublishingStudio::class)
        ->callAction(
            TestAction::make(ApproveAction::class)->table($workspace->fresh()),
            data: ['notes' => 'Second approval'],
        )
        ->assertHasNoActionErrors();

    expect($workspace->refresh()->status)->toBe(WorkspaceStatusEnum::Approved)
        ->and($workspace->approvals()->where('action', 'approved')->max('level'))->toBe(2);
});
