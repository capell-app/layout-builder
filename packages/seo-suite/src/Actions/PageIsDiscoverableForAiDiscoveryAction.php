<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Enums\TypeGroupEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Enums\RobotsDirectiveEnum;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static bool run(Page $page, Site $site, Language $language)
 */
final class PageIsDiscoverableForAiDiscoveryAction
{
    use AsAction;

    public function handle(Page $page, Site $site, Language $language): bool
    {
        return Page::query()
            ->whereKey($page->getKey())
            ->where('site_id', $site->getKey())
            ->withWhereHas(
                'pageUrl',
                fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language->getKey()),
            )
            ->withWhereHas(
                'type',
                fn (BuilderContract $query): BuilderContract => $query
                    ->where(
                        fn (Builder $typeQuery): Builder => $typeQuery->whereNull('group')
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
            ->exists();
    }
}
