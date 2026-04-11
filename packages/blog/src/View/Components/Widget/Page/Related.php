<?php

declare(strict_types=1);

namespace Capell\Blog\View\Components\Widget\Page;

use Capell\Blog\Support\Loader\TagLoader;
use Capell\Core\Models\Page;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Loader\PageLoader;
use Capell\Layout\View\Components\Widget\Page\AbstractPagesWidget;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class Related extends AbstractPagesWidget
{
    protected static string $defaultView = 'capell-layout::components.widget.asset.pages';

    protected function mountWidget(): void
    {
        $limit = $this->widget->meta['limit'] ?? config('capell-frontend.pagination_limit', 12);

        $page = Frontend::page();

        $tags = TagLoader::getPageTags($page);

        $tagIds = $tags->pluck('id')->toArray();

        $excludeParent = $page->hasPageHierarchy() && (bool) ($this->widget->meta['exclude_parent'] ?? false);

        $this->pages = PageLoader::getPages(
            language: Frontend::language(),
            site: Frontend::site(),
            limit: $limit,
            withChildrenCount: $page->type->meta['with_children_count'] ?? true,
            withImage: $this->widget->meta['with_image'] ?? false,
            withParent: $this->widget->meta['with_parent'] ?? false,
            withDate: $this->widget->meta['with_date'] ?? false,
            cacheKeyPrepend: 'tags-' . implode('-', $tagIds),
            morphModel: $this->widget->getMeta('page_model'),
            /**
             * @param  Builder<Page>  $query
             */
            modifyQuery: fn (Builder $query) => $query
                ->where('pages.id', '!=', $page->id)
                ->when(
                    $excludeParent && $page->parent_id,
                    fn (BuilderContract $query): BuilderContract => $query->where('pages.id', '!=', $page->parent_id),
                )
                ->whereHas(
                    'type',
                    fn (Builder $query): Builder => $query->enabled()
                        ->listable()
                        ->accessible()
                        ->when(
                            $this->widget->meta['exclude_types'] ?? false,
                            fn (BuilderContract $query): BuilderContract => $query->whereNotIn(
                                'types.key',
                                $this->widget->meta['exclude_types'] ?? [],
                            ),
                        ),
                )
                ->when(
                    $tags instanceof Collection && $tags->isNotEmpty(),
                    fn (Builder $query): Builder => $query->whereHas(
                        'tags',
                        fn (BuilderContract $query): BuilderContract => $query->whereIn('taggables.tag_id', $tagIds),
                    ),
                ),
        );

        $this->skipRender = $this->pages->isEmpty();
    }
}
