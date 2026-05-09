<?php

declare(strict_types=1);

namespace Capell\TranslationManager\Data;

use Spatie\LaravelData\Data;

final class TranslationWriteData extends Data
{
    /**
     * @param  array<string, string|null>  $values
     */
    public function __construct(
        public readonly TranslationSourceData $source,
        public readonly string $fileKey,
        public readonly string $locale,
        public readonly array $values,
    ) {}
}
