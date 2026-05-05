<?php

declare(strict_types=1);

namespace Capell\Migrator\Data;

/**
 * @phpstan-type ExternalImportRow array<string, mixed>
 */
final readonly class ExternalImportReadResult
{
    /**
     * @param  list<string>  $columns
     * @param  list<ExternalImportRow>  $rows
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $sourceType,
        public array $columns,
        public array $rows,
        public array $metadata = [],
        public string $suggestedTarget = 'page',
    ) {}

    public function count(): int
    {
        return count($this->rows);
    }
}
