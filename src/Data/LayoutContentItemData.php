<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

final class LayoutContentItemData extends Data
{
    /**
     * @param  array<string, mixed>  $editActionArguments
     * @param  array<string, mixed>  $widgetEditActionArguments
     * @param  array<int, string>  $warnings
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $key,
        public string $label,
        public ?string $summary,
        public string $typeLabel,
        public string $ownershipGroupKey,
        public string $ownershipGroupLabel,
        public string $sourceLabel,
        public ?string $sourceDetail,
        public ?string $renderedText,
        public ?string $renderedTextSourceLabel,
        public string $placementLabel,
        public string $containerKey,
        public string $containerLabel,
        public int $widgetIndex,
        public string $widgetLabel,
        public int $assetIndex,
        public string $assetType,
        public int|string|null $assetId,
        public bool $canEditAsset,
        public bool $isReused,
        public array $editActionArguments,
        public array $widgetEditActionArguments,
        public bool $hasWidgetCopySource,
        public array $warnings,
        public array $meta,
    ) {}
}
