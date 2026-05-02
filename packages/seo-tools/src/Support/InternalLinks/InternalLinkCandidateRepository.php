<?php

declare(strict_types=1);

namespace Capell\SeoTools\Support\InternalLinks;

use Capell\Core\Enums\TypeGroupEnum;
use Capell\Core\Enums\UrlTypeEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\SeoTools\Enums\RobotsDirectiveEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Throwable;

final class InternalLinkCandidateRepository
{
    /**
     * @return list<array{page_id:int,title:string,url:string,meta_title:?string,meta_description:?string}>
     */
    public function forPage(Page $page, Site $site, Language $language): array
    {
        return Page::query()
            ->where('site_id', $site->id)
            ->whereKeyNot($page->getKey())
            ->whereHas('translations', fn (Builder $query): Builder => $query->where('language_id', $language->id))
            ->whereHas('pageUrls', fn (Builder $query): Builder => $this->pageUrlQuery($query, $site, $language))
            ->whereHas(
                'type',
                fn (Builder $query): Builder => $query
                    ->where(
                        fn (Builder $query): Builder => $query->whereNull('group')
                            ->orWhereIn('group', config('capell.core.sitemap.type_groups', [TypeGroupEnum::Default->value])),
                    )
                    ->enabled()
                    ->visible()
                    ->accessible(),
            )
            ->where(
                fn (Builder $query): Builder => $query->whereNull('pages.meta')
                    ->orWhereJsonDoesntContain('pages.meta->hidden', true),
            )
            ->where(
                fn (Builder $query): Builder => $query->whereNull('pages.meta->robots')
                    ->orWhereJsonDoesntContain('pages.meta->robots', RobotsDirectiveEnum::NoIndex->value),
            )
            ->publishedDate()
            ->with([
                'translation' => fn (Builder|Relation $query): Builder|Relation => $query->where('language_id', $language->id),
                'pageUrls' => fn (Builder|Relation $query): Builder|Relation => $this->pageUrlQuery($query, $site, $language)
                    ->latest('id'),
                'pageUrls.siteDomain',
            ])
            ->get()
            ->map(fn (Page $candidatePage): ?array => $this->candidateData($candidatePage))
            ->filter()
            ->values()
            ->all();
    }

    private function pageUrlQuery(Builder|Relation $query, Site $site, Language $language): Builder|Relation
    {
        return $query
            ->where('site_id', $site->id)
            ->where('language_id', $language->id)
            ->where('status', true)
            ->whereNotNull('url')
            ->where('url', '!=', '')
            ->where(
                fn (Builder $query): Builder => $query->whereNull('type')
                    ->orWhere('type', '!=', UrlTypeEnum::Redirect),
            );
    }

    /**
     * @return array{page_id:int,title:string,url:string,meta_title:?string,meta_description:?string}|null
     */
    private function candidateData(Page $page): ?array
    {
        $translation = $page->translation;
        $pageUrl = $page->pageUrls->first();

        if (! $translation instanceof Translation || ! $pageUrl instanceof PageUrl) {
            return null;
        }

        $title = $this->stringValue($translation->title)
            ?? $this->stringValue($translation->label)
            ?? $this->stringValue($page->name);
        $url = $this->urlValue($pageUrl);

        if ($title === null || $url === null) {
            return null;
        }

        return [
            'page_id' => (int) $page->getKey(),
            'title' => $title,
            'url' => $url,
            'meta_title' => $this->stringValue($translation->meta_title),
            'meta_description' => $this->stringValue($translation->meta_description),
        ];
    }

    private function urlValue(PageUrl $pageUrl): ?string
    {
        $storedUrl = $this->stringValue($pageUrl->url);

        if ($storedUrl !== null) {
            return $storedUrl;
        }

        try {
            return $this->stringValue($pageUrl->full_url);
        } catch (Throwable) {
            return null;
        }
    }

    private function stringValue(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $stringValue = trim(strip_tags((string) $value));

        return $stringValue !== '' ? $stringValue : null;
    }
}
