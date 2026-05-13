<?php

declare(strict_types=1);

namespace Capell\SiteDiscovery\Support\Creator;

use Capell\Core\Actions\GetOrCreateResultsLayoutAction;
use Capell\Core\Contracts\ModelInterceptors\PageInterceptorInterface;
use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Core\Support\Creator\LayoutCreator;
use Capell\SiteDiscovery\Support\Sitemap\SitemapPageType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SitemapPageCreator
{
    /**
     * @var class-string<Layout>
     */
    protected string $layoutModel = Layout::class;

    /**
     * @var class-string<Page>
     */
    protected string $pageModel = Page::class;

    /**
     * @var class-string<Type>
     */
    protected string $typeModel = Type::class;

    public function createSitemapPage(Site $site, ?Collection $languages = null): Page
    {
        $languages ??= $site->languages;
        $type = $this->getOrCreateSitemapType();
        $layout = $this->getLayout(LayoutEnum::Results);

        $defaults = [
            'layout_id' => $layout->id,
            'site_id' => $site->id,
            'type_id' => $type->id,
            'name' => __('capell-site-discovery::generic.sitemap'),
        ];

        /** @var Page $page */
        $page = CapellCore::createOrUpdateModel(
            $this->pageModel,
            [
                'layout_id' => $layout->id,
                'site_id' => $site->id,
                'type_id' => $type->id,
            ],
            fn (array $data): array => CapellCore::mergeModelInterceptorData($defaults, $data),
            PageInterceptorInterface::class,
        );

        $languages->each(function (Language $language) use ($page): void {
            $translation = $page->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'meta' => [
                    'slug' => 'sitemap',
                ],
                'title' => __('capell-site-discovery::generic.sitemap'),
            ]);

            $page->pageUrl()->create([
                'url' => $page->getParentUrl(language: $language) . $translation->slug . '-xml',
                'language_id' => $language->id,
                'site_id' => $page->site_id,
                'type' => 'alias',
            ]);
        });

        return $page;
    }

    private function getOrCreateSitemapType(): Type
    {
        /** @var Builder<Type> $query */
        $query = $this->typeModel::query();

        $type = $query->where('key', SitemapPageType::Key)->pageType()->first();

        if ($type !== null) {
            return $type;
        }

        return SitemapPageType::createType();
    }

    private function getLayout(LayoutEnum $layoutEnum): Layout
    {
        if ($layoutEnum === LayoutEnum::Results) {
            return GetOrCreateResultsLayoutAction::run();
        }

        $layout = $this->layoutModel::query()->firstWhere('key', $layoutEnum->value);

        if ($layout !== null) {
            return $layout;
        }

        return resolve(LayoutCreator::class)->create($layoutEnum->value);
    }
}
