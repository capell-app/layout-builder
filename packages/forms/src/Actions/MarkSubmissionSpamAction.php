<?php

declare(strict_types=1);

namespace Capell\Forms\Actions;

use Capell\Forms\Enums\SubmissionStatus;
use Capell\Forms\Models\Submission;
use Lorisleiva\Actions\Concerns\AsAction;

class MarkSubmissionSpamAction
{
    use AsAction;

    public function handle(Submission $submission): Submission
    {
        $submission->forceFill(['status' => SubmissionStatus::Spam])->save();

        return $submission;
    }
}
