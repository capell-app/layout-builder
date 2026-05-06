<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Actions;

use Capell\FormBuilder\Enums\SubmissionStatus;
use Capell\FormBuilder\Models\Submission;
use Lorisleiva\Actions\Concerns\AsAction;

class MarkSubmissionReadAction
{
    use AsAction;

    public function handle(Submission $submission): Submission
    {
        $submission->forceFill(['status' => SubmissionStatus::Read])->save();

        return $submission;
    }
}
