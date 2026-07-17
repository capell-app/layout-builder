<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

final class LayoutBuilderStateData extends Data
{
    /**
     * @param  array<array-key, mixed>  $assets
     * @param  array<array-key, mixed>  $containers
     * @param  array<array-key, mixed>  $originalAssets
     * @param  array<array-key, mixed>  $selectedRecords
     */
    public function __construct(
        public array $containers,
        public array $assets,
        public array $originalAssets,
        public array $selectedRecords,
    ) {}

    /**
     * @param  array<array-key, mixed>  $assets
     * @param  array<array-key, mixed>  $containers
     * @param  array<array-key, mixed>  $originalAssets
     * @param  array<array-key, mixed>  $selectedRecords
     */
    public static function fromLivewire(
        ?array $containers,
        array $assets,
        ?array $originalAssets,
        array $selectedRecords,
    ): self {
        return new self(
            containers: $containers ?? [],
            assets: $assets,
            originalAssets: $originalAssets ?? [],
            selectedRecords: $selectedRecords,
        );
    }

    /**
     * @param  array<array-key, mixed>  $snapshot
     */
    public static function fromSnapshot(array $snapshot): self
    {
        return new self(
            containers: is_array($snapshot['containers'] ?? null) ? $snapshot['containers'] : [],
            assets: is_array($snapshot['assets'] ?? null) ? $snapshot['assets'] : [],
            originalAssets: is_array($snapshot['originalAssets'] ?? null) ? $snapshot['originalAssets'] : [],
            selectedRecords: is_array($snapshot['selectedRecords'] ?? null) ? $snapshot['selectedRecords'] : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function container(string $containerKey): array
    {
        $container = $this->containers[$containerKey] ?? null;

        return is_array($container) ? $container : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function containerMeta(string $containerKey): array
    {
        $meta = $this->container($containerKey)['meta'] ?? null;

        return is_array($meta) ? $meta : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function widgets(string $containerKey): array
    {
        $widgets = $this->container($containerKey)['widgets'] ?? null;

        if (! is_array($widgets)) {
            return [];
        }

        return $this->structuredItems($widgets);
    }

    /**
     * @return array<string, mixed>
     */
    public function widget(string $containerKey, int $widgetIndex): array
    {
        return $this->widgets($containerKey)[$widgetIndex] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function widgetSettings(string $containerKey, int $widgetIndex): array
    {
        $meta = $this->widget($containerKey, $widgetIndex)['meta'] ?? null;
        $settings = is_array($meta) ? ($meta['widget_settings'] ?? null) : null;

        return is_array($settings) ? $settings : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function assetSlot(string $containerKey, int $widgetIndex): array
    {
        return $this->structuredSlot($this->assets, $containerKey, $widgetIndex);
    }

    /**
     * @return array<int, mixed>
     */
    public function assetSlots(string $containerKey): array
    {
        return $this->slots($this->assets, $containerKey);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function originalAssetSlot(string $containerKey, int $widgetIndex): array
    {
        return $this->structuredSlot($this->originalAssets, $containerKey, $widgetIndex);
    }

    /**
     * @return list<mixed>
     */
    public function selectedRecordSlot(string $containerKey, int $widgetIndex): array
    {
        $containerSlots = $this->selectedRecords[$containerKey] ?? null;
        $slot = is_array($containerSlots) ? ($containerSlots[$widgetIndex] ?? null) : null;

        return is_array($slot) ? array_values($slot) : [];
    }

    /**
     * @return array<array-key, mixed>
     */
    public function toLivewirePayload(): array
    {
        return [
            'containers' => $this->containers,
            'assets' => $this->assets,
            'originalAssets' => $this->originalAssets,
            'selectedRecords' => $this->selectedRecords,
        ];
    }

    /**
     * @param  array<array-key, mixed>  $slots
     * @return array<int, mixed>
     */
    private function slots(array $slots, string $containerKey): array
    {
        $containerSlots = $slots[$containerKey] ?? null;

        return is_array($containerSlots) ? array_values($containerSlots) : [];
    }

    /**
     * @param  array<array-key, mixed>  $slots
     * @return list<array<string, mixed>>
     */
    private function structuredSlot(array $slots, string $containerKey, int $widgetIndex): array
    {
        $containerSlots = $slots[$containerKey] ?? null;
        $slot = is_array($containerSlots) ? ($containerSlots[$widgetIndex] ?? null) : null;

        if (! is_array($slot)) {
            return [];
        }

        return $this->structuredItems($slot);
    }

    /**
     * @param  array<array-key, mixed>  $items
     * @return list<array<string, mixed>>
     */
    private function structuredItems(array $items): array
    {
        $structuredItems = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $structuredItem = [];

            foreach ($item as $key => $value) {
                if (is_string($key)) {
                    $structuredItem[$key] = $value;
                }
            }

            $structuredItems[] = $structuredItem;
        }

        return $structuredItems;
    }
}
