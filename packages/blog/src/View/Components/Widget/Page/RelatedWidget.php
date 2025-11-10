<?php

declare(strict_types=1);

namespace Capell\Blog\View\Components\Widget\Page;

use Capell\Blog\Services\Loader\TagLoader;
use Capell\Core\Models\Page;
use Capell\Frontend\Facades\FrontendLoader;
use Capell\Frontend\Services\Loader\PageLoader;
use Capell\Layout\View\Components\Widget\Pages\AbstractPagesWidget;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class RelatedWidget extends AbstractPagesWidget
{
    protected function mountWidget(): void
    {
        $limit = $this->widget->meta['limit'] ?? config('capell-frontend.pagination_limit', 12);

        $page = FrontendLoader::getPage();

        $tags = TagLoader::getPageTags($page);

        $tagIds = $tags->pluck('id')->toArray();

        $this->pages = PageLoader::getPages(
            site: FrontendLoader::getSite(),
            language: FrontendLoader::getLanguage(),
            limit: $limit,
            withChildrenCount: $page->type->meta['with_children_count'] ?? true,
            withImage: $this->widget->meta['with_image'] ?? false,
            withParent: $this->widget->meta['with_parent'] ?? false,
            withDate: $this->widget->meta['with_date'] ?? false,
            cacheKeyPrepend: 'tags-' . implode('-', $tagIds),
            /**
             * @param  Builder<Page>  $query
             */
            modifyQuery: fn (Builder $query) => $query
                ->where('pages.id', '!=', $page->id)
                ->when(
                    $this->widget->meta['exclude_parent'] ?? false && $page->parent_id,
                    fn (BuilderContract $query) => $query->where('pages.id', '!=', $page->parent_id)
                )
                ->whereHas(
                    'type',
                    fn (Builder $query): Builder => $query->enabled()
                        ->listable()
                        ->accessible()
                        ->when(
                            $this->widget->meta['exclude_types'] ?? false,
                            fn (BuilderContract $query) => $query->whereNotIn(
                                'types.key',
                                $this->widget->meta['exclude_types'] ?? []
                            )
                        )
                )
                ->when(
                    $tags instanceof Collection && $tags->isNotEmpty(),
                    fn (Builder $query) => $query->whereHas(
                        'tags',
                        fn (BuilderContract $query) => $query->whereIn('taggables.tag_id', $tagIds)
                    )
                )
        );

        $this->skipRender = $this->pages->isEmpty();
    }
}
