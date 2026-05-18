<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

final class LayoutContentItemData extends Data
{
    /**
     * @param  array<string, mixed>  $editActionArguments
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $key,
        public string $label,
        public ?string $summary,
        public string $typeLabel,
        public string $placementLabel,
        public string $containerKey,
        public string $containerLabel,
        public int $blockIndex,
        public string $blockLabel,
        public int $assetIndex,
        public string $assetType,
        public int|string|null $assetId,
        public bool $isReused,
        public array $editActionArguments,
        public array $meta,
    ) {}
}
