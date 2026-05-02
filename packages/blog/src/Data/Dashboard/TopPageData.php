<?php

declare(strict_types=1);

namespace Capell\Blog\Data\Dashboard;

use Spatie\LaravelData\Data;

final class TopPageData extends Data
{
    public function __construct(
        public readonly string $path,
        public readonly int $views,
    ) {}
}
