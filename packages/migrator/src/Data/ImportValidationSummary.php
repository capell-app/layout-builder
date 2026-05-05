<?php

declare(strict_types=1);

namespace Capell\Migrator\Data;

/**
 * Dry-run summary produced by {@see BuildImportValidationSummaryAction}
 * and persisted to `import_sessions.validation_results`. Shown on the
 * Validate wizard step (H2.1 §6.4) as a final confirmation gate before
 * {@see ExecuteImportPlanJob} is dispatched.
 *
 * Stable shape — keys in {@see toArray()} are part of the persisted
 * contract and feed the "validation_results" JSON column.
 */
final readonly class ImportValidationSummary
{
    /**
     * @param  array{create: int, update: int, skip: int}  $pages
     * @param  array{match: int, create: int, clone: int, update: int, skip: int}  $relations
     * @param  array{import: int, reuse: int}  $media
     * @param  list<string>  $blockingErrors
     * @param  list<string>  $warnings
     */
    public function __construct(
        public array $pages,
        public array $relations,
        public array $media,
        public array $blockingErrors,
        public array $warnings,
        public string $generatedAt,
    ) {}

    public function isClean(): bool
    {
        return $this->blockingErrors === [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'pages' => $this->pages,
            'relations' => $this->relations,
            'media' => $this->media,
            'blocking_errors' => $this->blockingErrors,
            'warnings' => $this->warnings,
            'generated_at' => $this->generatedAt,
        ];
    }
}
