<?php

declare(strict_types=1);

use Capell\PublishingStudio\Actions\InstallWorkspaceRolesAction;
use Capell\PublishingStudio\Enums\WorkspaceApprovalActionEnum;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Enums\WorkspaceTransitionEnum;
use Capell\PublishingStudio\Events\WorkspaceStateChanged;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions\RequestChangesAction;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Pages\ManagePublishingStudio;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Notifications\WorkspaceStateNotification;
use Capell\Tests\Fixtures\Models\User;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;

use function Pest\Livewire\livewire;

use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class)
    ->group('publishing-studio');

beforeEach(function (): void {
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

it('returns workspace to Open and logs a ChangesRequested audit row', function (): void {
    $workspace = Workspace::factory()->inReview()->create(['name' => 'Needs tweaks']);

    $reviewer = User::factory()->create();
    $reviewer->assignRole(InstallWorkspaceRolesAction::ROLE_REVIEWER);

    $workspace->requestChanges($reviewer, 1, 'Please update the pricing section.');

    $workspace->refresh();

    expect($workspace->status)->toBe(WorkspaceStatusEnum::Open)
        ->and($workspace->submitted_at)->toBeNull();

    $latest = $workspace->approvals()->latest('id')->first();

    expect($latest)->not->toBeNull()
        ->and($latest->action)->toBe(WorkspaceApprovalActionEnum::ChangesRequested)
        ->and($latest->notes)->toBe('Please update the pricing section.')
        ->and($latest->actionable_id)->toBe($reviewer->getKey())
        ->and($latest->isChangesRequested())->toBeTrue();
});

it('dispatches a WorkspaceStateChanged event with ChangesRequested transition', function (): void {
    Event::fake([WorkspaceStateChanged::class]);

    $workspace = Workspace::factory()->inReview()->create();
    $reviewer = User::factory()->create();
    $reviewer->assignRole(InstallWorkspaceRolesAction::ROLE_REVIEWER);

    $workspace->requestChanges($reviewer, 1, 'Tighten the intro.');

    Event::assertDispatched(
        WorkspaceStateChanged::class,
        fn (WorkspaceStateChanged $event): bool => $event->workspace->is($workspace)
            && $event->transition === WorkspaceTransitionEnum::ChangesRequested->value
            && $event->notes === 'Tighten the intro.',
    );
});

it('notifies editors (not the reviewer) when changes are requested', function (): void {
    Notification::fake();

    $reviewer = User::factory()->create();
    $reviewer->assignRole(InstallWorkspaceRolesAction::ROLE_REVIEWER);

    $editor = User::factory()->create();
    $editor->assignRole(InstallWorkspaceRolesAction::ROLE_EDITOR);

    $workspace = Workspace::factory()->inReview()->create();

    test()->actingAs($reviewer);
    $workspace->requestChanges($reviewer, 1, 'Fix the broken image');

    Notification::assertSentTo($editor, WorkspaceStateNotification::class);
    Notification::assertNotSentTo($reviewer, WorkspaceStateNotification::class);
});

it('is callable via the Filament table action with required notes', function (): void {
    $workspace = Workspace::factory()->inReview()->create();

    $reviewer = User::factory()->create();
    $reviewer->assignRole(InstallWorkspaceRolesAction::ROLE_REVIEWER);
    $reviewer->givePermissionTo(['ViewAny:Workspace', 'View:Workspace']);

    test()->actingAs($reviewer);

    livewire(ManagePublishingStudio::class)
        ->callAction(
            TestAction::make(RequestChangesAction::class)->table($workspace),
            data: ['notes' => 'Add a stronger CTA above the fold.'],
        )
        ->assertHasNoActionErrors()
        ->assertNotified(__('capell-admin::workspace.notifications.changes_requested'));

    $workspace->refresh();
    expect($workspace->status)->toBe(WorkspaceStatusEnum::Open);

    $latest = $workspace->approvals()->latest('id')->first();
    expect($latest->action)->toBe(WorkspaceApprovalActionEnum::ChangesRequested)
        ->and($latest->notes)->toBe('Add a stronger CTA above the fold.');
});

it('requires notes to request changes (form validation)', function (): void {
    $workspace = Workspace::factory()->inReview()->create();

    $reviewer = User::factory()->create();
    $reviewer->assignRole(InstallWorkspaceRolesAction::ROLE_REVIEWER);
    $reviewer->givePermissionTo(['ViewAny:Workspace', 'View:Workspace']);

    test()->actingAs($reviewer);

    livewire(ManagePublishingStudio::class)
        ->callAction(
            TestAction::make(RequestChangesAction::class)->table($workspace),
            data: ['notes' => ''],
        )
        ->assertHasActionErrors(['notes']);

    expect($workspace->refresh()->status)->toBe(WorkspaceStatusEnum::InReview);
});

it('is hidden when the workspace is not in review', function (): void {
    $workspace = Workspace::factory()->open()->create();

    $reviewer = User::factory()->create();
    $reviewer->assignRole(InstallWorkspaceRolesAction::ROLE_REVIEWER);
    $reviewer->givePermissionTo(['ViewAny:Workspace', 'View:Workspace']);

    test()->actingAs($reviewer);

    livewire(ManagePublishingStudio::class)
        ->assertActionHidden(TestAction::make(RequestChangesAction::class)->table($workspace));
});
