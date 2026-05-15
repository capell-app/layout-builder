<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

class LayoutSidebarElementData extends Data
{
    /**
     * @param  array<int, string>  $layoutKeys
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly string $elementKey,
        public readonly array $layoutKeys = [],
        public readonly array $meta = [],
    ) {}

    public function appliesTo(string $layoutKey): bool
    {
        return $this->layoutKeys === [] || in_array($layoutKey, $this->layoutKeys, true);
    }

    /**
     * @return array{element_key: string, meta?: array<string, mixed>}
     */
    public function toLayoutElement(): array
    {
        $element = [
            'element_key' => $this->elementKey,
        ];

        if ($this->meta !== []) {
            $element['meta'] = $this->meta;
        }

        return $element;
    }
}
