<?php

declare(strict_types=1);

namespace Capell\Migrator\Data;

final readonly class ExternalImportPreview
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $errors
     */
    public function __construct(
        public string $target,
        public int $creates,
        public int $skips,
        public array $rows,
        public array $errors = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'target' => $this->target,
            'creates' => $this->creates,
            'skips' => $this->skips,
            'rows' => $this->rows,
            'errors' => $this->errors,
        ];
    }
}
