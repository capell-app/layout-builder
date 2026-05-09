<?php

declare(strict_types=1);

namespace Capell\TranslationManager\Data;

use Spatie\LaravelData\Data;

final class TranslationSourceData extends Data
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $sourcePath,
        public readonly string $overridePath,
        public readonly ?string $namespace,
        public readonly string $type,
        public readonly bool $sourceWritable,
    ) {}

    public function isNamespaced(): bool
    {
        return $this->namespace !== null && $this->namespace !== '';
    }
}
