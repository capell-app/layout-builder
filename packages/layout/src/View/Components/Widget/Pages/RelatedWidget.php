<?php

declare(strict_types=1);

namespace Capell\Layout\View\Components\Widget\Pages;

use Capell\Core\Models\Page;
use Capell\Core\Models\Type;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Services\Loader\PageLoader;
use Capell\Frontend\Services\Loader\TagLoader;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;

class RelatedWidget extends AbstractPagesWidget
{
    protected function mountWidget(): void
    {
        $limit = $this->widget->meta['limit'] ?? config('capell-frontend.pagination_limit', 12);

        $pageRecord = Frontend::getPage();

        $tags = TagLoader::getPageTags($pageRecord);

        $tagIds = $tags->pluck('id')->toArray();

        $this->pages = PageLoader::getPages(
            site: Frontend::getSite(),
            language: Frontend::getLanguage(),
            limit: $limit,
            withChildrenCount: $pageRecord->type->meta['with_children_count'] ?? true,
            withImage: $this->widget->meta['with_image'] ?? false,
            withParent: $this->widget->meta['with_parent'] ?? false,
            withDate: $this->widget->meta['with_date'] ?? false,
            cacheKeyPrepend: 'tags-'.implode('-', $tagIds),
            /**
             * @param  Page  $query
             */
            modifyQuery: fn (Builder $query) => $query
                ->where('pages.id', '!=', $pageRecord->id)
                ->when(
                    $this->widget->meta['exclude_parent'] ?? false && $pageRecord->parent_id,
                    fn (BuilderContract $query) => $query->where('pages.id', '!=', $pageRecord->parent_id)
                )
                ->whereHas(
                    'type',
                    /**
                     * @param  Type  $query
                     */
                    fn (BuilderContract $query) => $query->enabled()
                        ->visible()
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
                    $tags && $tags->isNotEmpty(),
                    fn (Builder $query) => $query->whereHas(
                        'tags',
                        fn (BuilderContract $query) => $query->whereIn('taggables.tag_id', $tagIds)
                    )
                )
        );

        $this->skipRender = $this->pages->isEmpty();
    }
}
