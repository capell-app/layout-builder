<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions;

use Capell\Core\Data\SchemaGraphData;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoTools\Enums\SchemaEntityTypeEnum;
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

        $schema = [
            '@type' => 'WebSite',
            '@id' => $siteUrl !== null && $siteUrl !== '' ? SchemaEntityTypeEnum::WebSite->toId($siteUrl) : null,
            'name' => $site->getMeta('business_name', $site->translation?->title),
            'url' => $siteUrl,
            'publisher' => $siteUrl !== null && $siteUrl !== '' ? ['@id' => SchemaEntityTypeEnum::Organization->toId($siteUrl)] : null,
        ];

        // Add SearchAction if site has a search/results page
        $searchPage = Page::getFirstPageByTypeForSite('results', $site, $language);

        if ($searchPage?->pageUrl?->full_url !== null && $searchPage?->pageUrl?->full_url !== '') {
            $schema['potentialAction'] = [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $searchPage->pageUrl->full_url . '?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ];
        }

        return array_filter($schema, static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function stripContext(array $schema): array
    {
        unset($schema['@context']);

        return $schema;
    }
}
