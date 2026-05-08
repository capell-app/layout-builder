<?php

declare(strict_types=1);

namespace Capell\AccessGate\Data;

final class RegistrationFieldValue
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $key,
        public readonly mixed $value,
        public readonly array $metadata = [],
    ) {}

    /**
     * @return array{value: mixed, metadata: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'metadata' => $this->metadata,
        ];
    }
}
