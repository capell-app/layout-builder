<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\LanguageFactory;
use Capell\Core\Database\Factories\PageFactory;
use Capell\Core\Database\Factories\SiteFactory;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\SeoSuite\Actions\BuildPageSeoReportAction;
use Capell\SeoSuite\Actions\BuildSchemaTemplateReportAction;
use Capell\SeoSuite\Contracts\SchemaTemplate;
use Capell\SeoSuite\Enums\SchemaTemplateTypeEnum;
use Capell\SeoSuite\Enums\SeoIssueSeverityEnum;
use Capell\SeoSuite\Support\SchemaTemplates\SchemaTemplateRegistry;

function schemaTemplateReportTestTemplate(string $schemaType, array $schema, array $requiredFields): SchemaTemplate
{
    return new class($schemaType, $schema, $requiredFields) implements SchemaTemplate
    {
        /**
         * @param  array<string, mixed>  $schema
         * @param  list<string>  $requiredFields
         */
        public function __construct(
            private string $schemaType,
            private array $schema,
            private array $requiredFields,
        ) {}

        public function build(Page $page, Site $site, Language $language): array
        {
            return ['@type' => $this->schemaType, ...$this->schema];
        }

        public function requiredFields(Page $page, Site $site, Language $language): array
        {
            return $this->requiredFields;
        }
    };
}

it('dashboard-dashboard_reports missing default template fields as warnings', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    $page = PageFactory::new()->site($site)->withTranslations($language)->create();
    $registry = new SchemaTemplateRegistry;
    $registry->register(
        SchemaTemplateTypeEnum::WebPage,
        schemaTemplateReportTestTemplate('WebPage', [], ['@type', 'url']),
    );
    app()->instance(SchemaTemplateRegistry::class, $registry);

    $dashboardReports = BuildSchemaTemplateReportAction::run($page, $site, $language);

    expect($dashboardReports)->toHaveCount(1)
        ->and($dashboardReports[0]->templateType)->toBe(SchemaTemplateTypeEnum::WebPage)
        ->and($dashboardReports[0]->presentFields)->toBe(['@type'])
        ->and($dashboardReports[0]->missingFields)->toBe(['url'])
        ->and($dashboardReports[0]->severity)->toBe(SeoIssueSeverityEnum::Warning);
});

it('dashboard-dashboard_reports missing fields as critical when the page type explicitly requires the schema type', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    $articleType = Type::factory()->page()->create(['meta' => ['schema' => ['type' => 'Article']]]);
    $page = PageFactory::new()->site($site)->type($articleType)->withTranslations($language)->create();
    $registry = new SchemaTemplateRegistry;
    $registry->register(
        SchemaTemplateTypeEnum::Article,
        schemaTemplateReportTestTemplate('Article', [], ['@type', 'headline']),
    );
    app()->instance(SchemaTemplateRegistry::class, $registry);

    $dashboardReports = BuildSchemaTemplateReportAction::run($page, $site, $language);

    expect($dashboardReports)->toHaveCount(1)
        ->and($dashboardReports[0]->templateType)->toBe(SchemaTemplateTypeEnum::Article)
        ->and($dashboardReports[0]->missingFields)->toBe(['headline'])
        ->and($dashboardReports[0]->severity)->toBe(SeoIssueSeverityEnum::Critical);
});

it('passes when all required template fields are present', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    $page = PageFactory::new()->site($site)->withTranslations($language)->create();
    $registry = new SchemaTemplateRegistry;
    $registry->register(
        SchemaTemplateTypeEnum::WebPage,
        schemaTemplateReportTestTemplate('WebPage', ['url' => 'https://example.test'], ['@type', 'url']),
    );
    app()->instance(SchemaTemplateRegistry::class, $registry);

    $dashboardReports = BuildSchemaTemplateReportAction::run($page, $site, $language);

    expect($dashboardReports[0]->presentFields)->toBe(['@type', 'url'])
        ->and($dashboardReports[0]->missingFields)->toBe([])
        ->and($dashboardReports[0]->severity)->toBe(SeoIssueSeverityEnum::Passed);
});

it('includes schema dashboard-dashboard_reports in the page SEO report', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    $page = PageFactory::new()
        ->site($site)
        ->withTranslations($language, [
            'meta' => [
                'title' => 'Useful Search Title',
                'description' => 'Useful search description for this report page.',
            ],
        ])
        ->create();
    $registry = new SchemaTemplateRegistry;
    $registry->register(
        SchemaTemplateTypeEnum::WebPage,
        schemaTemplateReportTestTemplate('WebPage', ['url' => 'https://example.test'], ['@type', 'url']),
    );
    app()->instance(SchemaTemplateRegistry::class, $registry);

    $report = BuildPageSeoReportAction::run($page, $site, $language);

    expect($report->schemaDashboardReports)->toHaveCount(1)
        ->and($report->schemaDashboardReports[0]->templateType)->toBe(SchemaTemplateTypeEnum::WebPage);
});
