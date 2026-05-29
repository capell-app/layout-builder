<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\FrontendAuthoring;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Layout;
use Capell\Core\Models\PageUrl;
use Capell\FrontendAuthoring\Data\EditableRegionPayloadData;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutBlockData;
use Illuminate\Database\Eloquent\Model;

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
            ->flatMap(fn (mixed $container): array => LayoutBlockData::fromContainer(is_array($container) ? $container : []))
            ->map(static fn (array $blockData): ?string => LayoutBlockData::key($blockData))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $widgets = Widget::query()
            ->whereIn('key', $widgetKeys)
            ->with('type')
            ->get()
            ->keyBy('key');

        foreach ($containers as $containerKey => $container) {
            foreach (LayoutBlockData::fromContainer(is_array($container) ? $container : []) as $blockIndex => $blockData) {
                $widgetKey = LayoutBlockData::key($blockData);
                $widget = $widgetKey === null ? null : $widgets->get($widgetKey);

                if (! $widget instanceof Widget) {
                    continue;
                }

                $regions[] = $this->blockRegion($pageUrl, $page, $layout, $widget, (string) $containerKey, (int) $blockIndex);
                $regions[] = $this->blockAssetsRegion($pageUrl, $page, $layout, $widget, (string) $containerKey, (int) $blockIndex);
            }
        }

        return $regions;
    }

    public static function blockSelector(int|string $layoutId, string $containerKey, int $blockIndex): string
    {
        return '#layout-block-' . hash('xxh128', $layoutId . ':' . $containerKey . ':' . $blockIndex);
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

    private function blockRegion(PageUrl $pageUrl, Model $page, Layout $layout, Widget $widget, string $containerKey, int $blockIndex): EditableRegionPayloadData
    {
        return $this->region(
            pageUrl: $pageUrl,
            page: $page,
            layout: $layout,
            recordKey: (int) $widget->getKey(),
            field: 'block',
            label: __('capell-layout-builder::button.edit_block') . ': ' . $widget->name,
            selector: self::blockSelector((int) $layout->getKey(), $containerKey, $blockIndex),
            regionKey: sprintf('layout.block.%s.%d', $containerKey, $blockIndex),
            target: sprintf('layout.block.%s.%d', $containerKey, $blockIndex),
            description: $widget->type?->name,
            containerKey: $containerKey,
            blockIndex: $blockIndex,
        );
    }

    private function blockAssetsRegion(PageUrl $pageUrl, Model $page, Layout $layout, Widget $widget, string $containerKey, int $blockIndex): EditableRegionPayloadData
    {
        return $this->region(
            pageUrl: $pageUrl,
            page: $page,
            layout: $layout,
            recordKey: (int) $widget->getKey(),
            field: 'assets',
            label: __('capell-layout-builder::button.show_block_assets') . ': ' . $widget->name,
            selector: self::blockSelector((int) $layout->getKey(), $containerKey, $blockIndex),
            regionKey: sprintf('layout.block-assets.%s.%d', $containerKey, $blockIndex),
            target: sprintf('layout.block.%s.%d', $containerKey, $blockIndex),
            description: __('capell-layout-builder::generic.assets'),
            containerKey: $containerKey,
            blockIndex: $blockIndex,
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
        ?int $blockIndex = null,
    ): EditableRegionPayloadData {
        return new EditableRegionPayloadData(
            model: $field === 'layout' ? Layout::class : Widget::class,
            recordKey: $recordKey,
            field: $field,
            label: $label,
            type: 'layout-builder',
            selector: $selector,
            currentUrl: $pageUrl->full_url,
            pageUrlId: (int) $pageUrl->getKey(),
            siteId: (int) $pageUrl->site_id,
            languageId: (int) $pageUrl->language_id,
            regionKey: $regionKey,
            surface: 'layout-builder',
            target: $target,
            description: $description,
            context: [
                'layoutId' => (int) $layout->getKey(),
                'siteId' => (int) ($pageUrl->site_id ?? 0),
                'pageId' => (int) $page->getKey(),
                'pageClass' => $page::class,
                'initialContainerKey' => $containerKey,
                'initialBlockIndex' => $blockIndex,
            ],
        );
    }
}
