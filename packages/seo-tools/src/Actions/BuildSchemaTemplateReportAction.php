<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoTools\Data\SchemaTemplateReportData;
use Capell\SeoTools\Enums\SchemaTemplateTypeEnum;
use Capell\SeoTools\Enums\SeoIssueSeverityEnum;
use Capell\SeoTools\Support\SchemaTemplates\SchemaTemplateRegistry;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static list<SchemaTemplateReportData> run(Page $page, Site $site, Language $language)
 */
class BuildSchemaTemplateReportAction
{
    use AsAction;

    /**
     * @return list<SchemaTemplateReportData>
     */
    public function handle(Page $page, Site $site, Language $language): array
    {
        $registry = resolve(SchemaTemplateRegistry::class);
        $reports = [];

        foreach ($registry->matching($page) as $type => $template) {
            $templateType = SchemaTemplateTypeEnum::from($type);
            $schema = $template->build($page, $site, $language);
            $requiredFields = $template->requiredFields($page, $site, $language);
            $presentFields = [];
            $missingFields = [];

            foreach ($requiredFields as $field) {
                if ($this->hasSchemaValue($schema, $field)) {
                    $presentFields[] = $field;

                    continue;
                }

                $missingFields[] = $field;
            }

            $reports[] = new SchemaTemplateReportData(
                templateType: $templateType,
                presentFields: $presentFields,
                missingFields: $missingFields,
                severity: $this->severity($missingFields, $registry->pageRequires($page, $templateType)),
            );
        }

        return $reports;
    }

    /**
     * @param  list<string>  $missingFields
     */
    private function severity(array $missingFields, bool $requiredByPageType): SeoIssueSeverityEnum
    {
        if ($missingFields === []) {
            return SeoIssueSeverityEnum::Passed;
        }

        return $requiredByPageType
            ? SeoIssueSeverityEnum::Critical
            : SeoIssueSeverityEnum::Warning;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function hasSchemaValue(array $schema, string $field): bool
    {
        if (! array_key_exists($field, $schema)) {
            return false;
        }

        if ($schema[$field] === null || $schema[$field] === '') {
            return false;
        }

        return ! (is_array($schema[$field]) && $schema[$field] === []);
    }
}
