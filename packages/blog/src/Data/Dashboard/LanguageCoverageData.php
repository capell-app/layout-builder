<?php

declare(strict_types=1);

namespace Capell\Blog\Data\Dashboard;

use Spatie\LaravelData\Data;

final class LanguageCoverageData extends Data
{
    public function __construct(
        public readonly string $language,
        public readonly int $withTranslation,
        public readonly int $withoutTranslation,
        public readonly int $total,
    ) {}
}
