<?php

declare(strict_types=1);

namespace Capell\TranslationManager\Data;

use Spatie\LaravelData\Data;

final class TranslationEntryData extends Data
{
    public function __construct(
        public readonly string $key,
        public readonly ?string $sourceValue,
        public readonly ?string $targetValue,
        public readonly string $status,
        public readonly bool $editable,
    ) {}
}
