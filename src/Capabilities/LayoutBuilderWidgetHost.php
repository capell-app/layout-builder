<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Capabilities;

use Capell\Core\Contracts\Capabilities\LayoutWidgetHost;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Data\Capabilities\LayoutWidgetMatchData;
use Capell\Core\Data\Capabilities\LayoutWidgetPlacementData;
use Capell\Core\Data\Capabilities\LayoutWidgetPlacementRequestData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Frontend\Facades\Frontend;
use Capell\LayoutBuilder\Actions\PlaceLayoutWidgetAction;
use Capell\LayoutBuilder\Actions\RegisterPreviewWidgetsAction;
use Capell\LayoutBuilder\Enums\ComponentTypeEnum;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\CapellLayoutManager;
use Capell\LayoutBuilder\Support\LayoutWidgetData;
use Capell\LayoutBuilder\Support\Livewire\OpaqueWidgetReference;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;
use Override;
use Throwable;

/**
 * Layout Builder's answer to Core's layout-widget-host capability, so packages preview, read and
 * invalidate layout widgets without naming Layout Builder's classes.
 */
final readonly class LayoutBuilderWidgetHost implements LayoutWidgetHost
{
    public function __construct(private LayoutLoader $loader) {}

    /**
     * @template TReturn
     *
     * @param  array<string, mixed>  $containers
     * @param  array<string, mixed>  $assets
     * @param  callable(bool): TReturn  $render
     * @return TReturn
     */
    #[Override]
    public function withPreviewWidgets(Page $page, Language $language, array $containers, array $assets, callable $render): mixed
    {
        // Cleared even when registration throws partway, so no half-registered preview
        // widget outlives this call in a long-lived worker.
        try {
            return $render(RegisterPreviewWidgetsAction::run($page, $language, $containers, $assets));
        } finally {
            CapellLayoutManager::clearContainerWidgets();
        }
    }

    #[Override]
    public function placement(string $reference): ?LayoutWidgetPlacementData
    {
        if ($reference === '') {
            return null;
        }

        $decoded = OpaqueWidgetReference::decode($reference);
        $containerKey = $decoded['container_key'] ?? null;
        $widgetKey = $decoded['widget_key'] ?? null;
        $occurrenceValue = $decoded['occurrence'] ?? null;
        $occurrence = is_numeric($occurrenceValue) ? max(1, (int) $occurrenceValue) : 1;
        $siteId = $decoded['site_id'] ?? null;

        if (! is_string($containerKey) || $containerKey === '' || ! is_string($widgetKey) || $widgetKey === '' || ! is_numeric($siteId)) {
            return null;
        }

        $site = $this->frontendValue(static fn (): mixed => Frontend::site());

        if (! $site instanceof Site || $site->id !== (int) $siteId) {
            return null;
        }

        $widget = $this->renderedWidget($containerKey, $widgetKey, $occurrence);

        if (! $widget instanceof Widget) {
            return null;
        }

        return new LayoutWidgetPlacementData(
            containerKey: $containerKey,
            widgetKey: $widgetKey,
            occurrence: $occurrence,
            meta: $this->resolvedMeta($widget),
        );
    }

    /**
     * @param  list<int>  $siteIds
     * @return list<int>
     */
    #[Override]
    public function pagesPlacing(LayoutWidgetMatchData $match, array $siteIds): array
    {
        $siteIds = array_values(array_unique(array_filter($siteIds, static fn (int $id): bool => $id > 0)));
        $values = array_values(array_unique(array_filter(array_map(trim(...), $match->values), static fn (string $value): bool => $value !== '')));

        if ($siteIds === [] || $values === []) {
            return [];
        }

        $widgetKeys = [];

        foreach (Widget::query()->with('blueprint')->get(['id', 'key', 'component', 'meta', 'blueprint_id']) as $widget) {
            if ($widget->getMetaComponent() === $match->component && in_array($widget->getMeta($match->metaKey), $values, true)) {
                $widgetKeys[] = (string) $widget->key;
            }
        }

        if ($widgetKeys === []) {
            return [];
        }

        $pages = Page::query()->whereIn('site_id', $siteIds)->whereNotNull('layout_id')->get(['id', 'layout_id']);
        $placingLayoutIds = [];

        foreach (Layout::query()->whereKey($pages->pluck('layout_id')->unique()->all())->get(['id', 'containers']) as $layout) {
            if ($this->placesAny($layout, $widgetKeys)) {
                $placingLayoutIds[] = $layout->id;
            }
        }

        $pageIds = [];

        foreach ($pages as $page) {
            if (in_array($page->layout_id, $placingLayoutIds, true)) {
                $pageIds[] = (int) $page->id;
            }
        }

        return $pageIds;
    }

    #[Override]
    public function offersComponent(string $component): bool
    {
        return in_array($component, CapellCore::getComponents(ComponentTypeEnum::Widget->name), true);
    }

    #[Override]
    public function placeWidget(Page $page, LayoutWidgetPlacementRequestData $request): bool
    {
        return PlaceLayoutWidgetAction::run($page, $request);
    }

    private function renderedWidget(string $containerKey, string $widgetKey, int $occurrence): ?Widget
    {
        $widget = CapellLayoutManager::getStoredContainerWidget($containerKey, $widgetKey, $occurrence);

        if ($widget instanceof Widget) {
            return $widget;
        }

        $layout = $this->frontendValue(static fn (): mixed => Frontend::layout());
        $language = $this->frontendValue(static fn (): mixed => Frontend::language());
        $page = $this->frontendValue(static fn (): mixed => Frontend::page());

        if (! $layout instanceof Layout || ! $language instanceof Language) {
            return null;
        }

        return $this->loader->getLayoutWidget(
            layout: $layout,
            widgetKey: $widgetKey,
            language: $language,
            page: $page instanceof Pageable ? $page : null,
            containerKey: $containerKey,
            occurrence: $occurrence,
            containerKeys: [$containerKey],
        );
    }

    /**
     * The widget's meta over its blueprint's, where the widget's own value is filled, as getMeta() reads it.
     *
     * @return array<string, mixed>
     */
    private function resolvedMeta(Widget $widget): array
    {
        $blueprint = $widget->getRelationValue('blueprint');
        $resolved = $blueprint instanceof Blueprint && is_array($blueprint->meta) ? $blueprint->meta : [];

        foreach (is_array($widget->meta) ? $widget->meta : [] as $key => $value) {
            if (is_string($key) && filled($value)) {
                $resolved[$key] = $value;
            }
        }

        return array_filter($resolved, is_string(...), ARRAY_FILTER_USE_KEY);
    }

    /** @param list<string> $widgetKeys */
    private function placesAny(Layout $layout, array $widgetKeys): bool
    {
        foreach (is_array($layout->containers) ? $layout->containers : [] as $container) {
            if (! is_array($container)) {
                continue;
            }

            $authored = [];

            foreach ($container as $name => $value) {
                if (is_string($name)) {
                    $authored[$name] = $value;
                }
            }

            foreach (LayoutWidgetData::fromContainer($authored) as $widget) {
                if (in_array(LayoutWidgetData::key($widget), $widgetKeys, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function frontendValue(callable $resolver): mixed
    {
        try {
            return $resolver();
        } catch (Throwable) {
            return null;
        }
    }
}
