<?php

declare(strict_types=1);

namespace Capell\Layout\View\Components\Widget;

use Capell\Core\Models;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Services\Loader\NavigationItemsLoader;
use Capell\Frontend\Services\Loader\NavigationLoader;

class Navigation extends AbstractWidget
{
    public array $items = [];

    public ?Models\Navigation $menu = null;

    protected static string $defaultView = 'capell-layout::components.widget.navigation.index';

    protected function mountWidget(): void
    {
        $menu = $this->getWidgetMenu();

        if ($menu instanceof Models\Navigation) {
            $this->menu = $menu;
        }

        if (! $this->menu instanceof Models\Navigation) {
            $this->skipRender = true;

            return;
        }

        $navigationLoader = new NavigationItemsLoader(
            navigation: $this->menu,
            site: Frontend::getSite(),
            language: Frontend::getLanguage(),
            siteDomain: Frontend::getSite()->siteDomain
        );

        $this->items = $navigationLoader->fetchMenuItems();

        $navigationLoader->activeMenuItems($this->items);
    }

    private function getWidgetMenu(): ?Models\Navigation
    {
        if (! empty($this->widget->meta['navigation_id'])) {
            return NavigationLoader::getNavigationById($this->widget->meta['navigation_id']);
        }

        if (empty($this->widget->meta['navigation'])) {
            return null;
        }

        $menu = NavigationLoader::getNavigation(
            $this->widget->meta['navigation'],
            Frontend::getSite(),
            Frontend::getLanguage()
        );

        if ($menu instanceof Models\Navigation) {
            return $menu;
        }

        return NavigationLoader::getNavigation(
            $this->widget->meta['navigation'],
            Frontend::getSite()
        );
    }
}
