<?php

declare(strict_types=1);

namespace Capell\MigrationAssistant\Services\Import;

final readonly class ManifestValidationReport
{
    /**
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     */
    public function __construct(
        public array $errors = [],
        public array $warnings = [],
    ) {}

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /**
     * @return array{errors: list<string>, warnings: list<string>}
     */
    public function toArray(): array
    {
        return ['errors' => $this->errors, 'warnings' => $this->warnings];
    }
}
