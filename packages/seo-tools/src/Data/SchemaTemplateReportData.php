<?php

declare(strict_types=1);

namespace Capell\SeoTools\Data;

use Capell\SeoTools\Enums\SchemaTemplateTypeEnum;
use Capell\SeoTools\Enums\SeoIssueSeverityEnum;
use Spatie\LaravelData\Data;

class SchemaTemplateReportData extends Data
{
    /**
     * @param  list<string>  $presentFields
     * @param  list<string>  $missingFields
     */
    public function __construct(
        public SchemaTemplateTypeEnum $templateType,
        public array $presentFields,
        public array $missingFields,
        public SeoIssueSeverityEnum $severity,
    ) {}
}
