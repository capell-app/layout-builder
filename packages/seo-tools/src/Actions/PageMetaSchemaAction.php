<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoTools\Enums\SchemaEntityTypeEnum;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static array run(Page $page, Site $site, Language $language)
 */
class PageMetaSchemaAction
{
    use AsAction;

    public function handle(Page $page, Site $site, Language $language): array
    {
        $page->loadMissing('translations.language');

        $configuratorType = $page->type->meta['schema']['type'] ?? 'WebPage';
        $pageUrl = $page->pageUrl?->full_url;
        $siteUrl = $site->siteDomain?->full_url;

        $entityType = SchemaEntityTypeEnum::fromSchemaType($configuratorType);

        $return = [
            '@context' => 'https://schema.org',
            '@type' => $configuratorType,
            '@id' => $pageUrl !== null && $pageUrl !== '' ? $entityType->toId($pageUrl) : null,
            'isPartOf' => $siteUrl !== null && $siteUrl !== '' ? ['@id' => SchemaEntityTypeEnum::WebSite->toId($siteUrl)] : null,
            'breadcrumb' => BreadcrumbsSchemaAction::run($page, $site, $language),
            'dateCreated' => $page->created_at?->toDateString(),
            'dateModified' => $page->updated_at?->toDateString(),
            'datePublished' => $page->visible_from?->toDateString(),
            'url' => $pageUrl,
            'creator' => data_get($page, 'creator.name'),
            'name' => $page->translation->label,
            'headline' => $page->translation->title,
            'availableLanguage' => $page->translations->pluck('language.name')->all(),
            'keywords' => $page->translation->meta_keywords,
            'description' => $page->translation->meta_description,
        ];

        if (in_array($configuratorType, ['Article', 'BlogPosting', 'NewsArticle', 'TechArticle', 'Report'], true)) {
            $return = array_merge($return, $this->articleFields($page, $site));
        }

        return array_filter($return, static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private function articleFields(Page $page, Site $site): array
    {
        $siteUrl = $site->siteDomain?->full_url;

        /** @var string|null $author */
        $author = data_get($page, 'creator.name');
        $imageUrl = $page->image?->getAvailableUrl(['large']);

        return [
            'author' => $author !== null && $author !== '' ? ['@type' => 'Person', 'name' => $author] : null,
            'publisher' => $siteUrl !== null && $siteUrl !== '' ? ['@id' => SchemaEntityTypeEnum::Organization->toId($siteUrl)] : null,
            'image' => $imageUrl,
        ];
    }
}
