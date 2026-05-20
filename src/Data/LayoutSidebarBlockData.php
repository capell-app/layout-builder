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
        public readonly string $blockKey,
        public readonly array $layoutKeys = [],
        public readonly array $meta = [],
    ) {}

    public function appliesTo(string $layoutKey): bool
    {
        return $this->layoutKeys === [] || in_array($layoutKey, $this->layoutKeys, true);
    }

    /**
     * @return array{block_key: string, meta?: array<string, mixed>}
     */
    public function toLayoutBlock(): array
    {
        $block = [
            'block_key' => $this->blockKey,
        ];

        if ($this->meta !== []) {
            $block['meta'] = $this->meta;
        }

        return $block;
    }
}
