<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\FrontendAuthoring;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Layout;
use Capell\Core\Models\PageUrl;
use Capell\FrontendAuthoring\Data\EditableRegionPayloadData;
use Capell\FrontendAuthoring\Enums\EditableRegionInputType;
use Capell\FrontendAuthoring\Enums\EditableRegionSurface;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutWidgetData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

final class LayoutBuilderEditableRegionContributor
{
    /**
     * @return list<EditableRegionPayloadData>
     */
    public function __invoke(PageUrl $pageUrl): array
    {
        $page = $pageUrl->pageable;

        if (! $page instanceof Model || ! $page instanceof Pageable) {
            return [];
        }

        $layoutId = (int) ($page->getAttribute('layout_id') ?? 0);
        if ($layoutId <= 0) {
            return [];
        }

        $layout = Layout::query()->find($layoutId);
        if (! $layout instanceof Layout) {
            return [];
        }

        $regions = [
            $this->pageLayoutRegion($pageUrl, $page, $layout),
        ];

        $containers = $layout->getAttribute('containers');
        $containers = is_array($containers) ? $containers : [];

        $widgetKeys = collect($containers)
            ->flatMap(fn (mixed $container): array => LayoutWidgetData::fromContainer(is_array($container) ? $container : []))
            ->map(static fn (array $widgetData): ?string => LayoutWidgetData::key($widgetData))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($widgetKeys === [] || ! Schema::hasTable('widgets')) {
            return $regions;
        }

        $widgets = Widget::query()
            ->whereIn('key', $widgetKeys)
            ->with('type')
            ->get()
            ->keyBy('key');

        foreach ($containers as $containerKey => $container) {
            foreach (LayoutWidgetData::fromContainer(is_array($container) ? $container : []) as $widgetIndex => $widgetData) {
                $widgetKey = LayoutWidgetData::key($widgetData);
                $widget = $widgetKey === null ? null : $widgets->get($widgetKey);

                if (! $widget instanceof Widget) {
                    continue;
                }

                $regions[] = $this->widgetRegion($pageUrl, $page, $layout, $widget, (string) $containerKey, (int) $widgetIndex);
                $regions[] = $this->widgetAssetsRegion($pageUrl, $page, $layout, $widget, (string) $containerKey, (int) $widgetIndex);
            }
        }

        return $regions;
    }

    public static function widgetSelector(int|string $layoutId, string $containerKey, int $widgetIndex): string
    {
        return '#layout-widget-' . hash('xxh128', $layoutId . ':' . $containerKey . ':' . $widgetIndex);
    }

    private function pageLayoutRegion(PageUrl $pageUrl, Model $page, Layout $layout): EditableRegionPayloadData
    {
        return $this->region(
            pageUrl: $pageUrl,
            page: $page,
            layout: $layout,
            recordKey: (int) $layout->getKey(),
            field: 'layout',
            label: __('capell-layout-builder::button.advanced_layout'),
            selector: config('capell-frontend-authoring.selectors.layout', '#main'),
            regionKey: 'layout.page',
            target: 'layout.page',
            description: __('capell-layout-builder::generic.content_first_editor'),
        );
    }

    private function widgetRegion(PageUrl $pageUrl, Model $page, Layout $layout, Widget $widget, string $containerKey, int $widgetIndex): EditableRegionPayloadData
    {
        return $this->region(
            pageUrl: $pageUrl,
            page: $page,
            layout: $layout,
            recordKey: (int) $widget->getKey(),
            field: 'widget',
            label: __('capell-layout-builder::button.edit_widget') . ': ' . $widget->name,
            selector: self::widgetSelector((int) $layout->getKey(), $containerKey, $widgetIndex),
            regionKey: sprintf('layout.widget.%s.%d', $containerKey, $widgetIndex),
            target: sprintf('layout.widget.%s.%d', $containerKey, $widgetIndex),
            description: $widget->type?->name,
            containerKey: $containerKey,
            widgetIndex: $widgetIndex,
        );
    }

    private function widgetAssetsRegion(PageUrl $pageUrl, Model $page, Layout $layout, Widget $widget, string $containerKey, int $widgetIndex): EditableRegionPayloadData
    {
        return $this->region(
            pageUrl: $pageUrl,
            page: $page,
            layout: $layout,
            recordKey: (int) $widget->getKey(),
            field: 'assets',
            label: __('capell-layout-builder::button.show_widget_assets') . ': ' . $widget->name,
            selector: self::widgetSelector((int) $layout->getKey(), $containerKey, $widgetIndex),
            regionKey: sprintf('layout.widget-assets.%s.%d', $containerKey, $widgetIndex),
            target: sprintf('layout.widget.%s.%d', $containerKey, $widgetIndex),
            description: __('capell-layout-builder::generic.assets'),
            containerKey: $containerKey,
            widgetIndex: $widgetIndex,
        );
    }

    private function region(
        PageUrl $pageUrl,
        Model $page,
        Layout $layout,
        int $recordKey,
        string $field,
        string $label,
        string $selector,
        string $regionKey,
        string $target,
        ?string $description,
        ?string $containerKey = null,
        ?int $widgetIndex = null,
    ): EditableRegionPayloadData {
        return new EditableRegionPayloadData(
            model: $field === 'layout' ? Layout::class : Widget::class,
            recordKey: $recordKey,
            field: $field,
            label: $label,
            type: EditableRegionInputType::Html,
            selector: $selector,
            currentUrl: $pageUrl->full_url,
            pageUrlId: (int) $pageUrl->getKey(),
            siteId: (int) $pageUrl->site_id,
            languageId: (int) $pageUrl->language_id,
            regionKey: $regionKey,
            surface: EditableRegionSurface::LayoutBuilder,
            target: $target,
            description: $description,
            context: [
                'layoutId' => (int) $layout->getKey(),
                'siteId' => (int) ($pageUrl->site_id ?? 0),
                'pageId' => (int) $page->getKey(),
                'pageClass' => $page::class,
                'initialContainerKey' => $containerKey,
                'initialWidgetIndex' => $widgetIndex,
            ],
        );
    }
}
