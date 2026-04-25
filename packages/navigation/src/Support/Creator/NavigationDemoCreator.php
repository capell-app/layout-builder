<?php

declare(strict_types=1);

namespace Capell\Navigation\Support\Creator;

use Capell\Core\Contracts\Navigation\DemoNavigationCreatorContract;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Navigation\Actions\AddPageToNavigationAction;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;

class NavigationDemoCreator implements DemoNavigationCreatorContract
{
    public function setupMainNavigation(Site $site, Language $language, Page $home, SupportCollection $pages): void
    {
        /** @var class-string<Type> $typeModel */
        $typeModel = Type::class;
        $navigationType = $typeModel::query()->navigationType()->default()->first();

        resolve(NavigationCreator::class)->mainNavigation(
            site: $site,
            type: $navigationType,
            language: $language,
            home: $home,
            additionalItems: $this->buildNavigationPageItems($pages, $language),
        );
    }

    public function setupFooterNavigation(Site $site, Language $language, SupportCollection $pages): void
    {
        /** @var class-string<Type> $typeModel */
        $typeModel = Type::class;
        $navigationType = $typeModel::query()->navigationType()->default()->first();

        resolve(NavigationCreator::class)->footerNavigation(
            site: $site,
            type: $navigationType,
            language: $language,
            items: $this->buildNavigationPageItems($pages, $language),
        );
    }

    public function setupSubFooterNavigation(Site $site, ?Language $language): void
    {
        /** @var class-string<Type> $typeModel */
        $typeModel = Type::class;
        $navigationType = $typeModel::query()->navigationType()->default()->first();

        resolve(NavigationCreator::class)->subFooterNavigation(
            site: $site,
            type: $navigationType,
            language: $language,
        );
    }

    /** @param SupportCollection<int, Site> $relatedSites */
    public function updateSubFooterNavigation(Site $site, SupportCollection $relatedSites): void
    {
        Navigation::query()
            ->where('site_id', $site->id)
            ->where('key', NavigationHandle::SubFooter->value)
            ->each(fn (Navigation $navigation) => $relatedSites->each(
                function (Site $relatedSite) use ($navigation): void {
                    $homepage = Page::getSiteHomePage($relatedSite);
                    AddPageToNavigationAction::run(
                        page: $homepage,
                        navigation: $navigation,
                        label: $relatedSite->translation->label,
                    );
                },
            ));
    }

    private function buildNavigationPageItems(SupportCollection $pages, Language $language): array
    {
        $this->loadPageTranslations($pages, $language);

        $items = [];

        foreach ($pages as $page) {
            $items[(string) Str::uuid()] = [
                'label' => NavigationCreator::getPageNavigationLabel($page, $language),
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'site_id' => $page->site_id,
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                ],
                'children' => $page->relationLoaded('children')
                    ? $this->buildNavigationPageItems($page->children, $language)
                    : [],
            ];
        }

        return $items;
    }

    private function loadPageTranslations(SupportCollection $pages, Language $language): void
    {
        if ($pages instanceof Collection) {
            $pages->loadMissing([
                'translations' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language->id),
            ]);
        }

        foreach ($pages as $page) {
            if (! $page instanceof Page || ! $page->relationLoaded('children')) {
                continue;
            }

            $children = $page->children;
            if (! $children instanceof Collection || $children->isEmpty()) {
                continue;
            }

            $this->loadPageTranslations($children, $language);
        }
    }
}
