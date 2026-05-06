<?php

declare(strict_types=1);

use Capell\PublishingStudio\Approvals\RecordReviewDecisionAction;
use Capell\PublishingStudio\Enums\ReviewDecisionEnum;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Events\WorkspaceStateChanged;
use Capell\PublishingStudio\Exceptions\InvalidReviewDecisionException;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Models\WorkspaceReviewAssignment;
use Illuminate\Support\Facades\Event;

function makeAssignment(Workspace $workspace, string $requiredFor = 'any'): WorkspaceReviewAssignment
{
    return WorkspaceReviewAssignment::query()->create([
        'workspace_id' => $workspace->id,
        'required_for' => $requiredFor,
    ]);
}

it('leaves workspace submitted while any assignment is outstanding', function (): void {
    $workspace = Workspace::factory()->create(['status' => WorkspaceStatusEnum::InReview]);
    $firstAssignment = makeAssignment($workspace);
    makeAssignment($workspace);

    $result = (new RecordReviewDecisionAction)->handle($firstAssignment, ReviewDecisionEnum::Approved);

    expect($result->status)->toBe(WorkspaceStatusEnum::InReview);
});

it('promotes workspace to Approved when the final outstanding decision is Approved', function (): void {
    Event::fake([WorkspaceStateChanged::class]);

    $workspace = Workspace::factory()->create(['status' => WorkspaceStatusEnum::InReview]);
    $firstAssignment = makeAssignment($workspace);
    $secondAssignment = makeAssignment($workspace);

    (new RecordReviewDecisionAction)->handle($firstAssignment, ReviewDecisionEnum::Approved);
    $result = (new RecordReviewDecisionAction)->handle($secondAssignment, ReviewDecisionEnum::Approved);

    expect($result->status)->toBe(WorkspaceStatusEnum::Approved)
        ->and($result->approved_at)->not->toBeNull();

    Event::assertDispatched(WorkspaceStateChanged::class);
});

it('rejects workspace back to Open on a single Rejected decision', function (): void {
    $workspace = Workspace::factory()->create(['status' => WorkspaceStatusEnum::InReview]);
    $assignment = makeAssignment($workspace);

    $result = (new RecordReviewDecisionAction)->handle($assignment, ReviewDecisionEnum::Rejected, null, 'blocking issue');

    expect($result->status)->toBe(WorkspaceStatusEnum::Open)
        ->and($result->submitted_at)->toBeNull();
});

it('refuses to record a decision twice on the same assignment', function (): void {
    $workspace = Workspace::factory()->create(['status' => WorkspaceStatusEnum::InReview]);
    $assignment = makeAssignment($workspace);
    (new RecordReviewDecisionAction)->handle($assignment, ReviewDecisionEnum::Approved);

    expect(fn (): Workspace => (new RecordReviewDecisionAction)->handle($assignment->fresh(), ReviewDecisionEnum::Approved))
        ->toThrow(InvalidReviewDecisionException::class);
});
