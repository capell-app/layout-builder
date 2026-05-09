<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Data;

use Spatie\LaravelData\Data;

class AiReadinessIssueData extends Data
{
    public function __construct(
        public string $key,
        public string $severity,
        public string $message,
        public ?int $pageId = null,
    ) {}
}
