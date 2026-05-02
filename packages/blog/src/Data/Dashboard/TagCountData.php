<?php

declare(strict_types=1);

namespace Capell\Blog\Data\Dashboard;

use Spatie\LaravelData\Data;

final class TagCountData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly int $articleCount,
    ) {}
}
