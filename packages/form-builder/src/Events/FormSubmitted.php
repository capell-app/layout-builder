<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Events;

use Capell\FormBuilder\Data\SubmissionMetaData;
use Capell\FormBuilder\Models\Form;
use Capell\FormBuilder\Models\Submission;
use Illuminate\Foundation\Events\Dispatchable;

class FormSubmitted
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function __construct(
        public Form $form,
        public ?Submission $submission = null,
        public ?SubmissionMetaData $metadata = null,
        public ?array $payload = null,
    ) {}
}
