<?php

declare(strict_types=1);

namespace Capell\SeoTools\Data;

use Spatie\LaravelData\Data;

class AiContentBriefData extends Data
{
    public function __construct(
        public string $contentAngle,
        public array $missingTopics,
        public array $suggestedHeadings,
        public array $faqIdeas,
        public array $schemaOpportunities,
        public array $internalLinks,
        public array $metaTitleAlternatives,
        public array $metaDescriptionAlternatives,
    ) {}
}
