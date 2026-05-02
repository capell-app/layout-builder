<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\LanguageFactory;
use Capell\Core\Database\Factories\PageFactory;
use Capell\Core\Database\Factories\SiteFactory;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\SeoTools\Actions\SchemaGraphAction;
use Capell\SeoTools\Contracts\SchemaTemplate;
use Capell\SeoTools\Enums\SchemaTemplateTypeEnum;
use Capell\SeoTools\Support\SchemaTemplates\SchemaTemplateRegistry;

it('does not duplicate article-compatible page schema nodes', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()
        ->recycle($language)
        ->language($language)
        ->withTranslations($language)
        ->create();
    $type = Type::factory()
        ->page()
        ->create(['meta' => ['schema' => ['type' => 'BlogPosting']]]);
    $page = PageFactory::new()
        ->site($site)
        ->type($type)
        ->published()
        ->withTranslations($language, [
            'title' => 'Schema template update',
            'meta' => [
                'description' => 'A useful schema template description.',
            ],
        ])
        ->create();

    $graph = SchemaGraphAction::run($page, $site->refresh(), $language);

    expect(collect($graph->nodes)->where('@type', 'BlogPosting'))->toHaveCount(1)
        ->and(collect($graph->nodes)->where('@type', 'Article'))->toHaveCount(0)
        ->and(collect($graph->nodes)->firstWhere('@type', 'BlogPosting')['breadcrumb'] ?? null)
        ->toHaveKey('@id');
});

it('does not discard richer package-owned schema templates with the same type', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()
        ->recycle($language)
        ->language($language)
        ->withTranslations($language)
        ->create();
    $type = Type::factory()
        ->page()
        ->create(['meta' => ['schema' => ['type' => 'Event']]]);
    $page = PageFactory::new()
        ->site($site)
        ->type($type)
        ->published()
        ->withTranslations($language, [
            'title' => 'Launch event',
            'meta' => [
                'description' => 'A useful event description.',
            ],
        ])
        ->create();
    $registry = new SchemaTemplateRegistry;
    $registry->register(SchemaTemplateTypeEnum::Event, new class implements SchemaTemplate
    {
        public function build(Page $page, Site $site, Language $language): array
        {
            return [
                '@type' => 'Event',
                'name' => 'Launch event',
                'startDate' => '2026-06-01',
            ];
        }

        public function requiredFields(Page $page, Site $site, Language $language): array
        {
            return ['@type', 'name', 'startDate'];
        }
    });
    app()->instance(SchemaTemplateRegistry::class, $registry);

    $graph = SchemaGraphAction::run($page, $site->refresh(), $language);

    expect(collect($graph->nodes)->where('@type', 'Event'))->toHaveCount(2)
        ->and(collect($graph->nodes)->contains('startDate', '2026-06-01'))->toBeTrue();
});
