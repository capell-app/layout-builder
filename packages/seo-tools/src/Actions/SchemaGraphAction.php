<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions;

use Capell\Core\Data\SchemaGraphData;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoTools\Enums\SchemaEntityTypeEnum;
use Capell\SeoTools\Support\SchemaTemplates\SchemaTemplateRegistry;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Collects all JSON-LD schemas for a page into a single @graph structure.
 *
 * @method static SchemaGraphData run(Page $page, Site $site, Language $language)
 */
class SchemaGraphAction
{
    use AsAction;

    public function handle(Page $page, Site $site, Language $language): SchemaGraphData
    {
        $nodes = [];

        // Organization schema
        $organizationSchema = SiteMetaSchemaAction::run($site, $language);
        $nodes[] = $this->stripContext($organizationSchema);

        // WebSite schema
        $websiteSchema = $this->buildWebSiteSchema($site, $language);
        $nodes[] = $websiteSchema;

        // WebPage / Article schema
        $pageSchema = PageMetaSchemaAction::run($page, $site, $language);
        $pageSchema = $this->stripContext($pageSchema);

        // Replace inline breadcrumb with @id reference
        if (isset($pageSchema['breadcrumb']) && is_array($pageSchema['breadcrumb'])) {
            $breadcrumbs = $pageSchema['breadcrumb'];
            $pageUrl = $page->pageUrl?->full_url;

            if ($pageUrl !== null && $pageUrl !== '') {
                $pageSchema['breadcrumb'] = ['@id' => SchemaEntityTypeEnum::BreadcrumbList->toId($pageUrl)];
            }

            // Add breadcrumb nodes separately
            foreach ($breadcrumbs as $breadcrumb) {
                if (is_array($breadcrumb)) {
                    $nodes[] = $this->stripContext($breadcrumb);
                }
            }
        }

        $nodes[] = $pageSchema;

        /** @var SchemaTemplateRegistry $schemaTemplateRegistry */
        $schemaTemplateRegistry = resolve(SchemaTemplateRegistry::class);
        $breadcrumbReference = $pageSchema['breadcrumb'] ?? null;

        foreach ($schemaTemplateRegistry->matching($page) as $template) {
            $templateSchema = $this->stripContext($template->build($page, $site, $language));
            $templateSchema = $this->normalizeTemplateBreadcrumb($templateSchema, $breadcrumbReference);

            if (! $this->isValidTemplateNode($templateSchema, $template->requiredFields($page, $site, $language))) {
                continue;
            }

            $nodes = $this->mergeTemplateNode($nodes, $templateSchema);
        }

        // Filter out any null @id values and empty nodes
        $nodes = array_map(
            fn (array $node): array => array_filter($node, static fn (mixed $value): bool => $value !== null),
            $nodes,
        );

        return new SchemaGraphData(nodes: array_values($nodes));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildWebSiteSchema(Site $site, Language $language): array
    {
        $siteUrl = $site->siteDomain?->full_url;

        $configurator = [
            '@type' => 'WebSite',
            '@id' => $siteUrl !== null && $siteUrl !== '' ? SchemaEntityTypeEnum::WebSite->toId($siteUrl) : null,
            'name' => $site->getMeta('business_name', $site->translation?->title),
            'url' => $siteUrl,
            'publisher' => $siteUrl !== null && $siteUrl !== '' ? ['@id' => SchemaEntityTypeEnum::Organization->toId($siteUrl)] : null,
        ];

        // Add SearchAction if site has a search/results page
        $searchPage = Page::getFirstPageByTypeForSite('results', $site, $language);

        if ($searchPage?->pageUrl?->full_url !== null && $searchPage?->pageUrl?->full_url !== '') {
            $configurator['potentialAction'] = [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $searchPage->pageUrl->full_url . '?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ];
        }

        return array_filter($configurator, static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $configurator
     * @return array<string, mixed>
     */
    private function stripContext(array $configurator): array
    {
        unset($configurator['@context']);

        return $configurator;
    }

    /**
     * @param  array<string, mixed>  $templateSchema
     * @return array<string, mixed>
     */
    private function normalizeTemplateBreadcrumb(array $templateSchema, mixed $breadcrumbReference): array
    {
        if (! is_array($breadcrumbReference)) {
            unset($templateSchema['breadcrumb']);

            return $templateSchema;
        }

        if (isset($breadcrumbReference['@id'])) {
            $templateSchema['breadcrumb'] = $breadcrumbReference;
        }

        return $templateSchema;
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @param  array<string, mixed>  $templateSchema
     * @return list<array<string, mixed>>
     */
    private function mergeTemplateNode(array $nodes, array $templateSchema): array
    {
        foreach ($nodes as $index => $node) {
            if (($templateSchema['@id'] ?? null) !== null && ($node['@id'] ?? null) === $templateSchema['@id']) {
                $nodes[$index] = array_replace_recursive($node, $templateSchema);

                return $nodes;
            }

            if (
                $this->isBuiltInPageSchemaType($templateSchema['@type'] ?? null)
                && $this->isBuiltInPageSchemaType($node['@type'] ?? null)
            ) {
                $nodes[$index] = array_replace_recursive($node, $templateSchema);

                return $nodes;
            }
        }

        $nodes[] = $templateSchema;

        return $nodes;
    }

    private function isBuiltInPageSchemaType(mixed $type): bool
    {
        return is_string($type) && in_array($type, [
            'Article',
            'BlogPosting',
            'NewsArticle',
            'TechArticle',
            'Report',
            'WebPage',
        ], true);
    }

    /**
     * @param  array<string, mixed>  $templateSchema
     * @param  list<string>  $requiredFields
     */
    private function isValidTemplateNode(array $templateSchema, array $requiredFields): bool
    {
        if ($templateSchema === []) {
            return false;
        }

        foreach ($requiredFields as $requiredField) {
            if (! array_key_exists($requiredField, $templateSchema)) {
                return false;
            }

            $value = $templateSchema[$requiredField];

            if ($value === null || $value === '') {
                return false;
            }

            if (is_array($value) && $value === []) {
                return false;
            }
        }

        return true;
    }
}
