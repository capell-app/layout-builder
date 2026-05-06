<?php

declare(strict_types=1);

namespace Capell\Search\Data;

use Spatie\LaravelData\Data;

final class SearchRequestData extends Data
{
    public function __construct(
        public string $query,
        public int $page = 1,
        public int $perPage = 10,
        public ?int $siteId = null,
        public ?int $languageId = null,
    ) {}
}
