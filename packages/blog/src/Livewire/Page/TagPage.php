<?php

declare(strict_types=1);

namespace Capell\Blog\Livewire\Page;

use Capell\Blog\Enums\ModelEnum;
use Capell\Blog\Models\Tag;
use Capell\Core\Facades\CapellCore;
use Capell\Frontend\Facades\CapellFrontend;
use Capell\Frontend\Facades\FrontendLoader;
use Capell\Frontend\Livewire\Page\AbstractPage;
use Capell\Frontend\Services\Loader\PageLoader;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Override;

class TagPage extends AbstractPage
{
    public ?string $tagSlug = null;

    protected static string $defaultView = 'capell::livewire.page.results';

    protected Tag $tag;

    protected ?string $tagName = null;

    #[Override]
    protected function getViewData(): array
    {
        return [
            'tag_slug' => $this->tagSlug,
            'tag_name' => $this->tagName,
        ];
    }

    protected function loadPage(): void
    {
        $this->tagSlug = FrontendLoader::getPageSlug();

        if ($this->tagSlug === '' || $this->tagSlug === '0') {
            CapellFrontend::throwErrorPage();

            return;
        }

        $language = FrontendLoader::getLanguage();
        $page = FrontendLoader::getPage();
        $site = FrontendLoader::getSite();

        /** @var class-string<Tag> $model */
        $model = CapellCore::getModel(ModelEnum::Tag);

        $tag = $model::query()->where('type', 'page')
            ->where('slug->' . $language->code, $this->tagSlug)
            ->first();

        if (! $tag) {
            CapellFrontend::throwErrorPage();

            return;
        }

        $this->tag = $tag;

        $this->tagName = $this->tag->getTranslation('name', $language->code);

        $paginationKey = config('capell-admin.page_query', 'pageQuery');

        $this->results = PageLoader::getPages(
            site: $site,
            language: $language,
            limit: $this->widget->meta['limit'] ?? config('capell-frontend.pagination_limit', 12),
            paginationPage: $this->getPage($paginationKey),
            withChildrenCount: $page->type->meta['with_children_count'] ?? true,
            withImage: $page->type->meta['with_image'] ?? true,
            withPagination: $page->type->meta['pagination'] ?? true,
            withParent: $page->type->meta['with_parent'] ?? true,
            withDate: $page->type->meta['with_date'] ?? true,
            paginationKey: $paginationKey,
            cacheKeyPrepend: 'tagged-' . $this->tag->id,
            modifyQuery: fn (Builder $query) => $query->whereHas(
                'tags',
                fn (BuilderContract $query) => $query->where('taggable_type', 'page')
                    ->where('taggables.tag_id', $this->tag->id),
            ),
        );

        $this->pageParams = $this->getViewData();

        FrontendLoader::setPageParams($this->pageParams);
    }
}
