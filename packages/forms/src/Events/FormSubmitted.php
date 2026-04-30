<?php

declare(strict_types=1);

namespace Capell\Forms\Events;

use Capell\Forms\Models\Form;
use Capell\Forms\Models\Submission;
use Illuminate\Foundation\Events\Dispatchable;

class FormSubmitted
{
    use Dispatchable;

    public function __construct(
        public Form $form,
        public Submission $submission,
    ) {}
}
