<?php

declare(strict_types=1);

namespace Capell\Forms\Enums;

use Capell\Forms\Filament\Resources\Forms\FormResource;
use Capell\Forms\Filament\Resources\Submissions\SubmissionResource;

enum ResourceEnum: string
{
    case Form = FormResource::class;
    case Submission = SubmissionResource::class;
}
