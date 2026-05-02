<?php

declare(strict_types=1);

use Capell\Forms\Actions\ArchiveSubmissionAction;
use Capell\Forms\Actions\MarkSubmissionReadAction;
use Capell\Forms\Actions\MarkSubmissionSpamAction;
use Capell\Forms\Enums\SubmissionStatus;
use Capell\Forms\Models\Submission;

it('marks a submission as read', function (): void {
    $submission = Submission::factory()->create(['status' => SubmissionStatus::New]);

    MarkSubmissionReadAction::run($submission);

    expect($submission->refresh()->status)->toBe(SubmissionStatus::Read);
});

it('archives a submission', function (): void {
    $submission = Submission::factory()->create(['status' => SubmissionStatus::Read]);

    ArchiveSubmissionAction::run($submission);

    expect($submission->refresh()->status)->toBe(SubmissionStatus::Archived);
});

it('marks a submission as spam', function (): void {
    $submission = Submission::factory()->create(['status' => SubmissionStatus::New]);

    MarkSubmissionSpamAction::run($submission);

    expect($submission->refresh()->status)->toBe(SubmissionStatus::Spam);
});
