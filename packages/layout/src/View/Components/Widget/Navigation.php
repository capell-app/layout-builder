<?php

declare(strict_types=1);

namespace Capell\Layout\View\Components\Widget;

use Capell\Core\Models;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Loader\NavigationItemsLoader;
use Capell\Frontend\Support\Loader\NavigationLoader;
use Illuminate\Support\Collection;

class Navigation extends AbstractWidget
{
    public ?Collection $items = null;

    public ?Models\Navigation $menu = null;

    protected static string $defaultView = 'capell-layout::components.widget.navigation.index';

    protected function mountWidget(): void
    {
        $menu = $this->getWidgetMenu();

        if (! $menu instanceof Models\Navigation) {
            if (config('capell-layout.widget.skip_render_empty', true)) {
                $this->skipRender = true;
            }

            return;
        }

        $this->menu = $menu;

        $navigationLoader = new NavigationItemsLoader(
            navigation: $this->menu,
            page: Frontend::page(),
            site: Frontend::site(),
            language: Frontend::language(),
            siteDomain: Frontend::site()->siteDomain,
        );

        $this->items = $navigationLoader->fetchMenuItems();

        if ($this->items->isEmpty()) {
            if (config('capell-layout.widget.skip_render_empty', true)) {
                $this->skipRender = true;
            }

            return;
        }

        $navigationLoader->activeMenuItems($this->items);
    }

    private function getWidgetMenu(): ?Models\Navigation
    {
        if (isset($this->widget->meta['navigation_id']) && is_numeric($this->widget->meta['navigation_id'])) {
            return NavigationLoader::getNavigationById($this->widget->meta['navigation_id']);
        }

        if (! isset($this->widget->meta['navigation']) || ! is_string($this->widget->meta['navigation'])) {
            return null;
        }

        return NavigationLoader::getNavigation(
            $this->widget->meta['navigation'],
            Frontend::site(),
            Frontend::language(),
        );
    }
}
