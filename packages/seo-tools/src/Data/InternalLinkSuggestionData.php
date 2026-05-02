<?php

declare(strict_types=1);

namespace Capell\SeoTools\Data;

use Spatie\LaravelData\Data;

class InternalLinkSuggestionData extends Data
{
    public function __construct(
        public int $pageId,
        public string $title,
        public string $url,
        public int $score,
        public string $reason,
    ) {}
}
