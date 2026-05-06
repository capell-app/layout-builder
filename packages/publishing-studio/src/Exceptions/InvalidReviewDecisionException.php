<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Exceptions;

use Capell\PublishingStudio\Models\WorkspaceReviewAssignment;
use RuntimeException;

class InvalidReviewDecisionException extends RuntimeException
{
    public static function alreadyDecided(WorkspaceReviewAssignment $assignment): self
    {
        return new self(sprintf(
            'Review assignment #%d has already been decided (%s).',
            $assignment->id,
            $assignment->decision?->value ?? 'unknown',
        ));
    }
}
