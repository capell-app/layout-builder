<?php

declare(strict_types=1);

use Capell\PublishingStudio\Actions\InstallWorkspaceRolesAction;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Enums\WorkspaceTransitionEnum;
use Capell\PublishingStudio\Events\WorkspaceStateChanged;
use Capell\PublishingStudio\Listeners\SendWorkspaceStateNotification;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Notifications\WorkspaceStateNotification;
use Capell\Tests\Fixtures\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    InstallWorkspaceRolesAction::run();
});

it('notifies reviewer and release manager on Submitted transition, excluding the actor', function (): void {
    Notification::fake();

    $actor = User::factory()->create();
    $actor->assignRole(InstallWorkspaceRolesAction::ROLE_EDITOR);

    $reviewer = User::factory()->create();
    $reviewer->assignRole(InstallWorkspaceRolesAction::ROLE_REVIEWER);

    $releaseManager = User::factory()->create();
    $releaseManager->assignRole(InstallWorkspaceRolesAction::ROLE_RELEASE_MANAGER);

    $workspace = Workspace::factory()->open()->create();

    (new SendWorkspaceStateNotification)->handle(new WorkspaceStateChanged(
        $workspace,
        WorkspaceStatusEnum::Open,
        WorkspaceStatusEnum::InReview,
        WorkspaceTransitionEnum::Submitted->value,
        $actor,
        'Please review',
    ));

    Notification::assertSentTo($reviewer, WorkspaceStateNotification::class);
    Notification::assertSentTo($releaseManager, WorkspaceStateNotification::class);
    Notification::assertNotSentTo($actor, WorkspaceStateNotification::class);
});

it('does nothing when notifications are disabled via config', function (): void {
    Notification::fake();

    config()->set('capell.publishing-studio.notifications.enabled', false);

    $reviewer = User::factory()->create();
    $reviewer->assignRole(InstallWorkspaceRolesAction::ROLE_REVIEWER);

    $workspace = Workspace::factory()->open()->create();

    (new SendWorkspaceStateNotification)->handle(new WorkspaceStateChanged(
        $workspace,
        WorkspaceStatusEnum::Open,
        WorkspaceStatusEnum::InReview,
        WorkspaceTransitionEnum::Submitted->value,
    ));

    Notification::assertNothingSent();
});

it('deduplicates users who carry more than one recipient role', function (): void {
    Notification::fake();

    $multiRole = User::factory()->create();
    $multiRole->assignRole(InstallWorkspaceRolesAction::ROLE_REVIEWER);
    $multiRole->assignRole(InstallWorkspaceRolesAction::ROLE_RELEASE_MANAGER);

    $workspace = Workspace::factory()->open()->create();

    (new SendWorkspaceStateNotification)->handle(new WorkspaceStateChanged(
        $workspace,
        WorkspaceStatusEnum::Open,
        WorkspaceStatusEnum::InReview,
        WorkspaceTransitionEnum::Submitted->value,
    ));

    Notification::assertSentToTimes($multiRole, WorkspaceStateNotification::class, 1);
});

it('routes Published transition to editors, reviewers and release managers', function (): void {
    Notification::fake();

    $editor = User::factory()->create();
    $editor->assignRole(InstallWorkspaceRolesAction::ROLE_EDITOR);

    $reviewer = User::factory()->create();
    $reviewer->assignRole(InstallWorkspaceRolesAction::ROLE_REVIEWER);

    $releaseManager = User::factory()->create();
    $releaseManager->assignRole(InstallWorkspaceRolesAction::ROLE_RELEASE_MANAGER);

    $workspace = Workspace::factory()->create(['status' => WorkspaceStatusEnum::Published]);

    (new SendWorkspaceStateNotification)->handle(new WorkspaceStateChanged(
        $workspace,
        WorkspaceStatusEnum::Approved,
        WorkspaceStatusEnum::Published,
        WorkspaceTransitionEnum::Published->value,
    ));

    Notification::assertSentTo($editor, WorkspaceStateNotification::class);
    Notification::assertSentTo($reviewer, WorkspaceStateNotification::class);
    Notification::assertSentTo($releaseManager, WorkspaceStateNotification::class);
});

it('routes Rejected transition only to editors', function (): void {
    Notification::fake();

    $editor = User::factory()->create();
    $editor->assignRole(InstallWorkspaceRolesAction::ROLE_EDITOR);

    $reviewer = User::factory()->create();
    $reviewer->assignRole(InstallWorkspaceRolesAction::ROLE_REVIEWER);

    $workspace = Workspace::factory()->open()->create();

    (new SendWorkspaceStateNotification)->handle(new WorkspaceStateChanged(
        $workspace,
        WorkspaceStatusEnum::InReview,
        WorkspaceStatusEnum::Open,
        WorkspaceTransitionEnum::Rejected->value,
    ));

    Notification::assertSentTo($editor, WorkspaceStateNotification::class);
    Notification::assertNotSentTo($reviewer, WorkspaceStateNotification::class);
});

it('sends nothing when the transition has no recipient roles configured', function (): void {
    Notification::fake();

    $workspace = Workspace::factory()->open()->create();

    (new SendWorkspaceStateNotification)->handle(new WorkspaceStateChanged(
        $workspace,
        WorkspaceStatusEnum::Open,
        WorkspaceStatusEnum::Abandoned,
        WorkspaceTransitionEnum::Abandoned->value,
    ));

    Notification::assertNothingSent();
});

it('renders a Submitted mail with an action-required subject and Review & Approve CTA', function (): void {
    $actor = User::factory()->create(['name' => 'Ada']);
    $reviewer = User::factory()->create();

    $workspace = Workspace::factory()->open()->create(['name' => 'Release April']);

    $notification = new WorkspaceStateNotification(
        $workspace,
        WorkspaceTransitionEnum::Submitted->value,
        $actor,
        'Please review by EOD',
    );

    $mail = $notification->toMail($reviewer);

    expect($mail->subject)->toContain('Action required')
        ->and($mail->subject)->toContain('Release April')
        ->and($mail->actionText)->toBe('Review & Approve')
        ->and($mail->level)->toBe('warning')
        ->and(implode(' ', $mail->introLines))
        ->toContain('Ada')
        ->and(implode(' ', $mail->introLines))
        ->toContain('submitted workspace')
        ->and(implode(' ', $mail->introLines))
        ->toContain('Please review by EOD');
});

it('renders non-submitted transitions with their transition-specific CTA', function (): void {
    $reviewer = User::factory()->create();
    $workspace = Workspace::factory()->approved()->create(['name' => 'Q2 Launch']);

    $approved = (new WorkspaceStateNotification(
        $workspace,
        WorkspaceTransitionEnum::Approved->value,
    ))->toMail($reviewer);

    $rejected = (new WorkspaceStateNotification(
        $workspace,
        WorkspaceTransitionEnum::Rejected->value,
    ))->toMail($reviewer);

    $published = (new WorkspaceStateNotification(
        $workspace,
        WorkspaceTransitionEnum::Published->value,
    ))->toMail($reviewer);

    expect($approved->actionText)->toBe('Publish workspace')
        ->and($rejected->actionText)->toBe('Edit workspace')
        ->and($published->actionText)->toBe('View on live');
});
