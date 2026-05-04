<?php

declare(strict_types=1);

namespace Capell\Navigation\View\Components;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Frontend\Facades\Frontend;
use Capell\Navigation\Actions\BuildNavigationRenderModelAction;
use Capell\Navigation\Data\NavigationRenderContextData;
use Capell\Navigation\Data\NavigationRenderData;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Support\Loader\NavigationLoader;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Menu extends Component
{
    public function __construct(
        public NavigationHandle|string $key,
        public ?Site $site = null,
        public ?Language $language = null,
        public ?Pageable $page = null,
        public ?SiteDomain $domain = null,
        public bool $siteOnlyFallback = true,
    ) {}

    public function render(): View|string
    {
        $menu = $this->resolveMenu();

        if (! $menu instanceof NavigationRenderData || $menu->isEmpty()) {
            return '';
        }

        return view('capell-navigation::components.menu', [
            'menu' => $menu,
        ]);
    }

    private function resolveMenu(): ?NavigationRenderData
    {
        $site = $this->site ?? Frontend::site();
        $language = $this->language ?? Frontend::language();
        $page = $this->page ?? Frontend::page();
        $siteDomain = $this->domain ?? $site?->siteDomain ?? $page?->pageUrl?->siteDomain;

        if (! $site instanceof Site || ! $language instanceof Language || ! $page instanceof Pageable || ! $siteDomain instanceof SiteDomain) {
            return null;
        }

        $navigation = NavigationLoader::getNavigation($this->key, $site, $language, $this->siteOnlyFallback);

        if ($navigation === null) {
            return null;
        }

        return BuildNavigationRenderModelAction::run(new NavigationRenderContextData(
            navigation: $navigation,
            page: $page,
            site: $site,
            language: $language,
            siteDomain: $siteDomain,
        ));
    }
}
