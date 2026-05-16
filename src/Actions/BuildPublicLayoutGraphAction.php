<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Contracts\PublicElementPayloadResolver;
use Capell\LayoutBuilder\Data\PublicLayoutContainerData;
use Capell\LayoutBuilder\Data\PublicLayoutElementData;
use Capell\LayoutBuilder\Data\PublicLayoutGraphData;
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Support\CapellLayoutManager;
use Capell\LayoutBuilder\Support\LayoutElementData;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsObject;

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
        $resolver = resolve(PublicElementPayloadResolver::class);

        $loader->preloadLayoutElements($layout, $language, $page, $selectedContainers);

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
        PublicElementPayloadResolver $resolver,
        string $containerKey,
        array $container,
        bool $includeHtml,
        ?array $selectedContainers,
    ): PublicLayoutContainerData {
        $elements = LayoutElementData::normalizeMany($container['elements'] ?? []);

        return new PublicLayoutContainerData(
            key: $containerKey,
            meta: [],
            elements: collect($elements)
                ->map(fn (mixed $elementData): ?PublicLayoutElementData => $this->elementData(
                    layout: $layout,
                    page: $page,
                    language: $language,
                    loader: $loader,
                    resolver: $resolver,
                    containerKey: $containerKey,
                    elementData: $elementData,
                    includeHtml: $includeHtml,
                    selectedContainers: $selectedContainers,
                ))
                ->filter()
                ->values()
                ->all(),
        );
    }

    /**
     * @param  array<string, mixed>  $elementData
     * @param  array<int, string>|null  $selectedContainers
     */
    private function elementData(
        Layout $layout,
        Page $page,
        Language $language,
        LayoutLoader $loader,
        PublicElementPayloadResolver $resolver,
        string $containerKey,
        array $elementData,
        bool $includeHtml,
        ?array $selectedContainers,
    ): ?PublicLayoutElementData {
        $elementKey = LayoutElementData::key($elementData);
        if ($elementKey === null) {
            return null;
        }

        $occurrence = LayoutElementData::occurrence($elementData);
        $element = CapellLayoutManager::getStoredContainerElement($containerKey, $elementKey, $occurrence)
            ?? $loader->getLayoutElement($layout, $elementKey, $language, $page, $containerKey, $occurrence, $selectedContainers);

        if (! $element instanceof Element) {
            return null;
        }

        $publicElement = $this->elementWithPublicOccurrenceMeta($element, $elementData);

        return new PublicLayoutElementData(
            key: $elementKey,
            occurrence: $occurrence,
            type: $element->type?->key,
            data: $resolver->data($publicElement, $page, $language, $containerKey, $occurrence),
            html: $includeHtml ? $resolver->html($publicElement, $page, $language, $containerKey, $occurrence) : null,
        );
    }

    /**
     * @param  array<string, mixed>  $elementData
     */
    private function elementWithPublicOccurrenceMeta(Element $element, array $elementData): Element
    {
        $occurrenceMeta = is_array($elementData['meta'] ?? null) ? $elementData['meta'] : [];
        $safeOccurrenceMeta = $this->safePublicBlockMeta($occurrenceMeta);
        $baseMeta = is_array($element->meta) ? $element->meta : [];

        $publicElement = clone $element;
        $publicElement->setAttribute('meta', array_replace_recursive($this->safePublicBlockMeta($baseMeta), $safeOccurrenceMeta));

        return $publicElement;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private function safePublicBlockMeta(array $meta): array
    {
        $safeMeta = [];

        foreach (['block_key', 'block_variant'] as $key) {
            $value = $meta[$key] ?? null;

            if ($this->isSafeIdentifier($value)) {
                $safeMeta[$key] = trim($value);
            }
        }

        $settings = is_array($meta['block_settings'] ?? null) ? $meta['block_settings'] : [];
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
            $safeMeta['block_settings'] = $safeSettings;
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
