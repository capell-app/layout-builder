<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Contracts\PublicLayoutWidgetPayloadResolver;
use Capell\LayoutBuilder\Data\PublicLayoutContainerData;
use Capell\LayoutBuilder\Data\PublicLayoutGraphData;
use Capell\LayoutBuilder\Data\PublicLayoutWidgetData;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\CapellLayoutManager;
use Capell\LayoutBuilder\Support\LayoutWidgetData;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static PublicLayoutGraphData run(Layout $layout, Page $page, Language $language, array<int, string> $containers = [], bool $includeHtml = false)
 */
class BuildPublicLayoutGraphAction
{
    use AsObject;

    /**
     * @param  array<int, string>  $containers
     */
    public function handle(Layout $layout, Page $page, Language $language, array $containers = [], bool $includeHtml = false): PublicLayoutGraphData
    {
        $layoutContainers = $layout->getAttribute('containers');
        $layoutContainers = is_array($layoutContainers) ? $layoutContainers : [];

        $page->setRelation('layout', $layout);
        $this->hydrateSiteRelation($layout, $page);

        $selectedContainers = $this->selectedContainers($containers);
        $loader = resolve(LayoutLoader::class);
        $resolver = resolve(PublicLayoutWidgetPayloadResolver::class);

        $loader->preloadLayoutWidgets($layout, $language, $page, $selectedContainers);

        return new PublicLayoutGraphData(
            key: $layout->key,
            meta: [],
            containers: collect($layoutContainers)
                ->filter(fn (mixed $container, string|int $containerKey): bool => $this->shouldIncludeContainer((string) $containerKey, $selectedContainers))
                ->map(fn (mixed $container, string|int $containerKey): PublicLayoutContainerData => $this->containerData(
                    layout: $layout,
                    page: $page,
                    language: $language,
                    loader: $loader,
                    resolver: $resolver,
                    containerKey: (string) $containerKey,
                    container: is_array($container) ? $container : [],
                    includeHtml: $includeHtml,
                    selectedContainers: $selectedContainers,
                ))
                ->values()
                ->all(),
        );
    }

    /**
     * @param  array<string, mixed>  $container
     * @param  array<int, string>|null  $selectedContainers
     */
    private function containerData(
        Layout $layout,
        Page $page,
        Language $language,
        LayoutLoader $loader,
        PublicLayoutWidgetPayloadResolver $resolver,
        string $containerKey,
        array $container,
        bool $includeHtml,
        ?array $selectedContainers,
    ): PublicLayoutContainerData {
        $widgets = LayoutWidgetData::fromContainer($container);

        return new PublicLayoutContainerData(
            key: $containerKey,
            meta: [],
            widgets: collect($widgets)
                ->map(fn (mixed $widgetData): ?PublicLayoutWidgetData => $this->widgetData(
                    layout: $layout,
                    page: $page,
                    language: $language,
                    loader: $loader,
                    resolver: $resolver,
                    containerKey: $containerKey,
                    widgetData: $widgetData,
                    includeHtml: $includeHtml,
                    selectedContainers: $selectedContainers,
                ))
                ->filter()
                ->values()
                ->all(),
        );
    }

    /**
     * @param  array<string, mixed>  $widgetData
     * @param  array<int, string>|null  $selectedContainers
     */
    private function widgetData(
        Layout $layout,
        Page $page,
        Language $language,
        LayoutLoader $loader,
        PublicLayoutWidgetPayloadResolver $resolver,
        string $containerKey,
        array $widgetData,
        bool $includeHtml,
        ?array $selectedContainers,
    ): ?PublicLayoutWidgetData {
        $widgetKey = LayoutWidgetData::key($widgetData);
        if ($widgetKey === null) {
            return null;
        }

        $occurrence = LayoutWidgetData::occurrence($widgetData);
        $widget = CapellLayoutManager::getStoredContainerWidget($containerKey, $widgetKey, $occurrence)
            ?? $loader->getLayoutWidget($layout, $widgetKey, $language, $page, $containerKey, $occurrence, $selectedContainers);

        if (! $widget instanceof Widget) {
            return null;
        }

        $publicWidget = $this->widgetWithPublicOccurrenceMeta($widget, $widgetData);

        return new PublicLayoutWidgetData(
            key: $widgetKey,
            occurrence: $occurrence,
            type: $widget->blueprint?->key,
            data: $resolver->data($publicWidget, $page, $language, $containerKey, $occurrence),
            html: $includeHtml ? $resolver->html($publicWidget, $page, $language, $containerKey, $occurrence) : null,
        );
    }

    /**
     * @param  array<string, mixed>  $widgetData
     */
    private function widgetWithPublicOccurrenceMeta(Widget $widget, array $widgetData): Widget
    {
        $occurrenceMeta = is_array($widgetData['meta'] ?? null) ? $widgetData['meta'] : [];
        $safeOccurrenceMeta = $this->safePublicWidgetMeta($occurrenceMeta);
        $baseMeta = is_array($widget->meta) ? $widget->meta : [];

        $publicWidget = clone $widget;
        $publicWidget->setAttribute('meta', array_replace_recursive($this->safePublicWidgetMeta($baseMeta), $safeOccurrenceMeta));

        return $publicWidget;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private function safePublicWidgetMeta(array $meta): array
    {
        $safeMeta = [];

        foreach (['widget_key', 'widget_variant'] as $key) {
            $value = $meta[$key] ?? null;

            if ($this->isSafeIdentifier($value)) {
                $safeMeta[$key] = trim((string) $value);
            }
        }

        foreach (['show_home', 'show_parent', 'show_current_page'] as $key) {
            $value = $this->safeBoolean($meta[$key] ?? null);

            if ($value !== null) {
                $safeMeta[$key] = $value;
            }
        }

        $minimumItems = $meta['minimum_items'] ?? null;
        if (is_int($minimumItems)) {
            $safeMeta['minimum_items'] = max(1, min(10, $minimumItems));
        } elseif (is_string($minimumItems) && ctype_digit($minimumItems)) {
            $safeMeta['minimum_items'] = max(1, min(10, (int) $minimumItems));
        }

        $settings = is_array($meta['widget_settings'] ?? null) ? $meta['widget_settings'] : [];
        $safeSettings = [];

        $this->putAllowedSetting($safeSettings, 'spacing', $settings['spacing'] ?? null, ['tight', 'normal', 'spacious']);
        $this->putAllowedSetting($safeSettings, 'background', $settings['background'] ?? null, ['default', 'muted', 'dark', 'image']);
        $this->putAllowedSetting($safeSettings, 'media_position', $settings['media_position'] ?? null, ['left', 'right', 'top']);
        $this->putAllowedSetting($safeSettings, 'heading_width', $settings['heading_width'] ?? null, ['narrow', 'normal', 'wide']);

        $cardsPerRow = $settings['cards_per_row'] ?? null;
        if (is_int($cardsPerRow)) {
            $safeSettings['cards_per_row'] = max(1, min(6, $cardsPerRow));
        } elseif (is_string($cardsPerRow) && ctype_digit($cardsPerRow)) {
            $safeSettings['cards_per_row'] = max(1, min(6, (int) $cardsPerRow));
        }

        $showCta = $settings['show_cta'] ?? null;
        if (is_bool($showCta)) {
            $safeSettings['show_cta'] = $showCta;
        }

        $anchorId = $this->safeAnchorId($settings['anchor_id'] ?? null);
        if ($anchorId !== null) {
            $safeSettings['anchor_id'] = $anchorId;
        }

        if ($safeSettings !== []) {
            $safeMeta['widget_settings'] = $safeSettings;
        }

        return $safeMeta;
    }

    private function hydrateSiteRelation(Layout $layout, Page $page): void
    {
        if ($page->relationLoaded('site')) {
            return;
        }

        $layoutSite = $layout->relationLoaded('site') ? $layout->getRelation('site') : null;
        if ($layoutSite instanceof Site) {
            $page->setRelation('site', $layoutSite);

            return;
        }

        $siteId = $page->getAttribute('site_id');
        if (! is_int($siteId) && ! is_string($siteId)) {
            return;
        }

        $site = Site::query()->find($siteId);
        if ($site instanceof Site) {
            $page->setRelation('site', $site);
        }
    }

    private function isSafeIdentifier(mixed $value): bool
    {
        return is_string($value)
            && ! $this->containsUnsafePublicMarker($value)
            && preg_match('/\A[a-z0-9][a-z0-9._-]{0,127}\z/i', trim($value)) === 1;
    }

    private function safeBoolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return match ($value) {
                0 => false,
                1 => true,
                default => null,
            };
        }

        if (! is_string($value)) {
            return null;
        }

        return match (strtolower(trim($value))) {
            '0', 'false', 'no', 'off' => false,
            '1', 'true', 'yes', 'on' => true,
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $safeSettings
     * @param  array<int, string>  $allowed
     */
    private function putAllowedSetting(array &$safeSettings, string $key, mixed $value, array $allowed): void
    {
        if (is_string($value) && in_array($value, $allowed, true)) {
            $safeSettings[$key] = $value;
        }
    }

    private function safeAnchorId(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        if ($this->containsUnsafePublicMarker($value)) {
            return null;
        }

        if (preg_match('/[:\/?#&=@\\\\]/', $value) === 1) {
            return null;
        }

        $anchorId = Str::slug($value);

        return $anchorId === '' || strlen($anchorId) > 80 ? null : $anchorId;
    }

    private function containsUnsafePublicMarker(string $value): bool
    {
        $tokens = preg_split('/[^a-z0-9]+/i', strtolower($value), flags: PREG_SPLIT_NO_EMPTY);

        if (! is_array($tokens)) {
            return true;
        }

        return array_intersect($tokens, [
            'admin',
            'authoring',
            'filament',
            'permission',
            'permissions',
            'schema',
            'secret',
            'selector',
            'signed',
            'token',
            'url',
        ]) !== [];
    }

    /**
     * @param  array<int, string>  $containers
     * @return array<int, string>|null
     */
    private function selectedContainers(array $containers): ?array
    {
        if ($containers === [] || in_array('*', $containers, true)) {
            return null;
        }

        return array_values(array_unique($containers));
    }

    /**
     * @param  array<int, string>|null  $selectedContainers
     */
    private function shouldIncludeContainer(string $containerKey, ?array $selectedContainers): bool
    {
        return $selectedContainers === null || in_array($containerKey, $selectedContainers, true);
    }
}
