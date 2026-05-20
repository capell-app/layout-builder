<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

final class LayoutFragmentData extends Data
{
    /**
     * @param  array<string, mixed>|null  $container
     * @param  array<string, mixed>|null  $block
     * @param  array<int, array<string, mixed>>  $assets
     * @param  array<int, array<string, mixed>>  $originalAssets
     * @param  array<int, mixed>  $selectedRecords
     */
    public function __construct(
        public readonly string $sourceContainerKey,
        public readonly ?int $sourceBlockIndex,
        public readonly ?array $container,
        public readonly ?array $block,
        public readonly array $assets = [],
        public readonly array $originalAssets = [],
        public readonly array $selectedRecords = [],
    ) {}

    public function isContainerFragment(): bool
    {
        return $this->container !== null && $this->sourceBlockIndex === null;
    }

    public function isBlockFragment(): bool
    {
        return $this->block !== null && $this->sourceBlockIndex !== null;
    }
}
