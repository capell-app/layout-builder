<?php

declare(strict_types=1);

namespace Capell\Search\Data;

use Spatie\LaravelData\Data;

/**
 * Data object representing a single search hit.
 */
final class SearchResultData extends Data
{
    public function __construct(
        public readonly string $title,
        public readonly string $url,
        public readonly string $excerpt,
        public readonly string $type = 'page',
        public readonly float $score = 0.0,
    ) {}
}
