<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class AdminWidgetPreviewData extends Data
{
    public function __construct(
        public string $view,
        public string $label,
        public ?string $title,
        public ?string $excerpt,
        public ?Media $image,
        public ?string $typeLabel,
        public ?string $icon,
        public int $assetCount,
        public bool $hasPageAssets,
        public bool $usesPageContent,
    ) {}
}
