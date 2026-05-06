<?php

declare(strict_types=1);

use Capell\FormBuilder\Actions\ArchiveSubmissionAction;
use Capell\FormBuilder\Actions\MarkSubmissionReadAction;
use Capell\FormBuilder\Actions\MarkSubmissionSpamAction;
use Capell\FormBuilder\Enums\SubmissionStatus;
use Capell\FormBuilder\Models\Submission;

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
