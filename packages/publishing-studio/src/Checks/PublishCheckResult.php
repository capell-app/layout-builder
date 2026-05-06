<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Checks;

use Spatie\LaravelData\Data;

/**
 * Outcome of a single {@see PublishCheck}. `messages` describes what was
 * found; `entityRefs` is a free-form list of entity identifiers (model
 * class + uuid + optional field path) so the UI can link each finding to
 * the offending row.
 */
class PublishCheckResult extends Data
{
    /**
     * @param  array<int, string>  $messages
     * @param  array<int, array{model?: string, uuid?: string, field?: string}>  $entityRefs
     */
    public function __construct(
        public string $identifier,
        public string $label,
        public PublishCheckSeverity $severity,
        public array $messages = [],
        public array $entityRefs = [],
    ) {}

    public function isError(): bool
    {
        return $this->severity === PublishCheckSeverity::Error;
    }

    public function isClean(): bool
    {
        return $this->messages === [] && $this->entityRefs === [];
    }
}
