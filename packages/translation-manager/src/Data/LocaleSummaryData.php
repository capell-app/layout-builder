<?php

declare(strict_types=1);

namespace Capell\TranslationManager\Data;

use Spatie\LaravelData\Data;

final class LocaleSummaryData extends Data
{
    public function __construct(
        public readonly string $locale,
        public readonly int $fileCount,
        public readonly bool $sourceAvailable,
        public readonly bool $overrideAvailable,
    ) {}
}
