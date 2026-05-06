<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Data;

use Spatie\LaravelData\Data;

class RedirectOpportunityData extends Data
{
    public function __construct(
        public string $sourceUrl,
        public int $hits,
        public ?int $siteId,
        public ?int $languageId,
        public ?string $suggestedTargetUrl,
        public ?string $pageName,
    ) {}
}
