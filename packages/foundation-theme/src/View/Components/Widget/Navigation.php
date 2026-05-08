<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\View\Components\Widget;

use Capell\Frontend\Facades\Frontend;
use Capell\Navigation\Actions\BuildNavigationRenderModelAction;
use Capell\Navigation\Data\NavigationRenderContextData;
use Capell\Navigation\Data\NavigationRenderData;
use Capell\Navigation\Models;
use Capell\Navigation\Support\Loader\NavigationLoader;
use Illuminate\Support\Collection;

class Navigation extends AbstractWidget
{
    public ?Collection $items = null;

    public ?Models\Navigation $menu = null;

    public ?NavigationRenderData $navigationRenderData = null;

    protected static string $defaultView = 'capell-layout-builder::components.widget.navigation.index';

    protected function mountWidget(): void
    {
        $menu = $this->getWidgetMenu();

        if (! $menu instanceof Models\Navigation) {
            if (config('capell-layout-builder.widget.skip_render_empty', true) === true) {
                $this->skipRender = true;
            }

            return;
        }

        $this->menu = $menu;

        $this->navigationRenderData = BuildNavigationRenderModelAction::run(new NavigationRenderContextData(
            navigation: $this->menu,
            page: Frontend::page(),
            site: Frontend::site(),
            language: Frontend::language(),
            siteDomain: Frontend::site()->siteDomain,
        ));

        $this->items = $this->navigationRenderData->items;

        if ($this->items->isEmpty()) {
            if (config('capell-layout-builder.widget.skip_render_empty', true) === true) {
                $this->skipRender = true;
            }

            return;
        }
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
