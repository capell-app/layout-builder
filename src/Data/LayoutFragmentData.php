<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

final class LayoutFragmentData extends Data
{
    /**
     * @param  array<string, mixed>|null  $container
     * @param  array<string, mixed>|null  $element
     * @param  array<int, array<string, mixed>>  $assets
     * @param  array<int, array<string, mixed>>  $originalAssets
     * @param  array<int, mixed>  $selectedRecords
     */
    public function __construct(
        public readonly string $sourceContainerKey,
        public readonly ?int $sourceElementIndex,
        public readonly ?array $container,
        public readonly ?array $element,
        public readonly array $assets = [],
        public readonly array $originalAssets = [],
        public readonly array $selectedRecords = [],
    ) {}

    public function isContainerFragment(): bool
    {
        return $this->container !== null && $this->sourceElementIndex === null;
    }

    public function isElementFragment(): bool
    {
        return $this->element !== null && $this->sourceElementIndex !== null;
    }
}
