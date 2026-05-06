<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Data;

use Capell\SeoSuite\Enums\SeoCheckKeyEnum;
use Capell\SeoSuite\Enums\SeoIssueSeverityEnum;
use Spatie\LaravelData\Data;

class SeoIssueData extends Data
{
    public function __construct(
        public SeoCheckKeyEnum $key,
        public SeoIssueSeverityEnum $severity,
        public string $message,
        public ?string $actionLabel = null,
        public ?string $actionUrl = null,
    ) {}
}
