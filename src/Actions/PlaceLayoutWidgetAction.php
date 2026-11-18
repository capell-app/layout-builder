<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Admin\Actions\ReplicateLayoutAction;
use Capell\Core\Data\Capabilities\LayoutWidgetPlacementRequestData;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Enums\WidgetTypeEnum;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Creator\TypeCreator;
use Capell\LayoutBuilder\Support\LayoutWidgetData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Puts a widget another package asked for on one page, straight after the page's own content.
 * A layout other pages share is copied for this page first, so the widget never appears on pages
 * it was not placed on. Placing the same widget again changes nothing. Returns false when the
 * page's layout has nowhere to put a widget.
 *
 * @method static bool run(Page $page, LayoutWidgetPlacementRequestData $request)
 */
final class PlaceLayoutWidgetAction
{
    use AsFake;
    use AsObject;

    private const string PageContentWidgetKey = 'page-content';

    public function handle(Page $page, LayoutWidgetPlacementRequestData $request): bool
    {
        // Two live widgets may share a key (the unique index treats a null deleted_at as
        // distinct), so choosing the next free key and committing it happen under one lock.
        $placed = Cache::lock('capell-layout-builder:widget-key:' . $request->keyPrefix, 30)
            ->block(10, fn (): bool => $this->place($page, $request));

        return $placed === true;
    }

    private function place(Page $page, LayoutWidgetPlacementRequestData $request): bool
    {
        return DB::transaction(function () use ($page, $request): bool {
            $widget = $this->widget($request);
            $layout = $this->pageOwnLayout($page, $request);

            if (! $layout instanceof Layout) {
                return false;
            }

            $containers = is_array($layout->containers) ? $layout->containers : [];

            foreach ($containers as $container) {
                if (is_array($container) && $this->containsWidget($container, $widget->key)) {
                    return true;
                }
            }

            $containerKey = $this->targetContainerKey($containers);

            if ($containerKey === null) {
                return false;
            }

            $container = is_array($containers[$containerKey]) ? $containers[$containerKey] : [];
            $container['widgets'] = $this->widgetsWith(LayoutWidgetData::fromContainer($container), $widget->key);
            $containers[$containerKey] = $container;

            $layout->update(['containers' => $containers]);

            return true;
        });
    }

    /**
     * Widgets are shared by every site. An existing enabled widget with the same component and
     * match value is reused untouched. One an editor repointed, changed or disabled is never
     * edited: a new widget with the next free key is created instead.
     */
    private function widget(LayoutWidgetPlacementRequestData $request): Widget
    {
        for ($attempt = 1; ; $attempt++) {
            $key = $attempt === 1 ? $request->keyPrefix : $request->keyPrefix . '-' . $attempt;
            $existing = Widget::query()->where('key', $key)->first();

            if (! $existing instanceof Widget) {
                break;
            }

            if ($this->matches($existing, $request)) {
                return $existing;
            }
        }

        $widget = new Widget;
        $widget->fill([
            'key' => $key,
            'name' => $request->name,
            'blueprint_id' => $this->widgetBlueprint()->getKey(),
            'component' => $request->component,
            'is_livewire' => true,
            'meta' => $request->meta,
            'admin' => $request->configurator === null ? [] : ['configurator' => $request->configurator],
            'status' => true,
        ])->save();

        return $widget;
    }

    private function matches(Widget $widget, LayoutWidgetPlacementRequestData $request): bool
    {
        $value = $widget->getMeta($request->matchKey);

        return (bool) $widget->status
            && $widget->getMetaComponent() === $request->component
            && is_scalar($value)
            && (string) $value === $request->matchValue();
    }

    private function widgetBlueprint(): Blueprint
    {
        $blueprint = Blueprint::query()
            ->where('type', LayoutTypeEnum::Widget->value)
            ->where('key', WidgetTypeEnum::Default->value)
            ->first();

        return $blueprint instanceof Blueprint ? $blueprint : resolve(TypeCreator::class)->defaultWidgetType();
    }

    /**
     * The page keeps its layout when no other page uses it; otherwise it gets its own copy so the
     * widget stays on this page only.
     */
    private function pageOwnLayout(Page $page, LayoutWidgetPlacementRequestData $request): ?Layout
    {
        $layout = $page->layout()->first();

        if (! $layout instanceof Layout) {
            return null;
        }

        $shared = (bool) $layout->default || Page::query()
            ->where('layout_id', $layout->getKey())
            ->whereKeyNot($page->getKey())
            ->exists();

        if (! $shared) {
            return $layout;
        }

        $copy = ReplicateLayoutAction::run($layout);
        $copy->forceFill(['name' => $request->layoutName, 'default' => false])->save();
        $page->forceFill(['layout_id' => $copy->getKey()])->save();

        return $copy;
    }

    /**
     * @param  array<array-key, mixed>  $containers
     */
    private function targetContainerKey(array $containers): ?string
    {
        foreach ($containers as $containerKey => $container) {
            if (is_array($container) && $this->containsWidget($container, self::PageContentWidgetKey)) {
                return (string) $containerKey;
            }
        }

        foreach ($containers as $containerKey => $container) {
            if (is_array($container)) {
                return (string) $containerKey;
            }
        }

        return null;
    }

    /**
     * @param  array<array-key, mixed>  $container
     */
    private function containsWidget(array $container, string $widgetKey): bool
    {
        foreach (LayoutWidgetData::fromContainer($container) as $widget) {
            if (LayoutWidgetData::key($widget) === $widgetKey) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<string, mixed>>  $widgets
     * @return list<array<string, mixed>>
     */
    private function widgetsWith(array $widgets, string $widgetKey): array
    {
        $placedWidget = ['widget_key' => $widgetKey, 'occurrence' => 1];
        $placed = [];
        $inserted = false;

        foreach ($widgets as $widget) {
            $placed[] = $widget;

            if (! $inserted && LayoutWidgetData::key($widget) === self::PageContentWidgetKey) {
                $placed[] = $placedWidget;
                $inserted = true;
            }
        }

        if (! $inserted) {
            $placed[] = $placedWidget;
        }

        return $placed;
    }
}
