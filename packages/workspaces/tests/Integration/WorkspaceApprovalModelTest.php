<?php

declare(strict_types=1);

use Capell\Tests\Fixtures\Models\User;
use Capell\Workspaces\Enums\WorkspaceApprovalActionEnum;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Models\WorkspaceApproval;

it('belongs to a workspace', function (): void {
    $workspace = Workspace::factory()->create();
    $approval = WorkspaceApproval::factory()->workspace($workspace)->submitted()->create();

    expect($approval->workspace)->toBeInstanceOf(Workspace::class)
        ->and($approval->workspace->id)->toBe($workspace->id);
});

it('resolves the polymorphic actionable relation', function (): void {
    $user = User::factory()->create();
    $approval = WorkspaceApproval::factory()->actionable($user)->create();

    expect($approval->actionable)->not->toBeNull()
        ->and($approval->actionable->getKey())->toBe($user->id);
});

it('classifies approval records by action', function (): void {
    $submission = WorkspaceApproval::factory()->submitted()->create();
    $approval = WorkspaceApproval::factory()->approved()->create();
    $rejection = WorkspaceApproval::factory()->rejected()->create();

    expect($submission->isSubmission())->toBeTrue()
        ->and($submission->isApproval())->toBeFalse()
        ->and($submission->isRejection())->toBeFalse()
        ->and($approval->isApproval())->toBeTrue()
        ->and($rejection->isRejection())->toBeTrue();
});

it('casts action to WorkspaceApprovalActionEnum', function (): void {
    $approval = WorkspaceApproval::factory()->approved()->create();

    expect($approval->action)->toBe(WorkspaceApprovalActionEnum::Approved);
});

it('casts level to integer', function (): void {
    $approval = WorkspaceApproval::factory()->level(2)->create();

    expect($approval->level)->toBe(2);
});
