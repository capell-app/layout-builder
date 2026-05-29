<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

class LayoutSidebarBlockData extends Data
{
    /**
     * @param  array<int, string>  $layoutKeys
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly string $widgetKey,
        public readonly array $layoutKeys = [],
        public readonly array $meta = [],
    ) {}

    public function appliesTo(string $layoutKey): bool
    {
        return $this->layoutKeys === [] || in_array($layoutKey, $this->layoutKeys, true);
    }

    /**
     * @return array{widget_key: string, meta?: array<string, mixed>}
     */
    public function toLayoutBlock(): array
    {
        $block = [
            'widget_key' => $this->widgetKey,
        ];

        if ($this->meta !== []) {
            $block['meta'] = $this->meta;
        }

        return $block;
    }
}
