<?php

declare(strict_types=1);

namespace Capell\Blog\Livewire\Page;

use Capell\Blog\Models\Article;
use Capell\Blog\Support\Loader\TagLoader;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Livewire\Page\AbstractPage;
use Capell\Frontend\Support\Loader\PageLoader;
use Capell\Frontend\Support\State\FrontendState;
use Capell\Tags\Models\Tag as TagModel;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Override;

class Tag extends AbstractPage
{
    public ?string $tagSlug = null;

    protected static string $defaultView = 'capell::livewire.page.results';

    protected TagModel $tag;

    protected ?string $tagName = null;

    protected function setup(): void
    {
        $params = Frontend::params();
        $this->tagSlug = is_array($params) ? ($params['tag'] ?? null) : null;

        abort_if(in_array($this->tagSlug, ['', '0', null], true), 404);

        $language = Frontend::language();
        $page = Frontend::page();
        $site = Frontend::site();

        $tag = TagLoader::tagPage($this->tagSlug, $site, $language);

        abort_unless($tag, 404);

        $this->tag = $tag;

        $this->tagName = $this->tag->getTranslation('name', $language->code);

        $paginationPage = config('capell-admin.page_query', 'pageQuery');

        $model = Article::class;

        $this->results = PageLoader::getPages(
            language: $language,
            site: $site,
            limit: $page->meta['limit'] ?? $page->type->meta['limit'] ?? config('capell-frontend.pagination_limit', 12),
            paginationPage: (int) $this->getPage($paginationPage),
            withImage: $page->type->meta['with_image'] ?? true,
            withPagination: $page->type->meta['pagination'] ?? true,
            withDate: $page->type->meta['with_date'] ?? true,
            paginationKey: 'tag-pages',
            cacheKeyPrepend: 'tagged-' . $this->tag->id,
            morphModel: $model,
            modifyQuery: fn (Builder $query) => $query->whereHas(
                'tags',
                fn (BuilderContract $query): BuilderContract => $query->where(
                    'taggable_type',
                    Relation::getMorphAlias($model),
                )
                    ->where('taggables.tag_id', $this->tag->id),
            ),
        );

        $this->params = $this->getViewData();

        resolve(FrontendState::class)->withParams($this->params);
    }

    #[Override]
    protected function getViewData(): array
    {
        return [
            'tag_slug' => $this->tagSlug,
            'tag_name' => $this->tagName,
        ];
    }
}
