<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

final class WidgetPickerPreviewData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $imageUrl,
        public readonly string $icon,
        public readonly bool $disabled,
    ) {}
}
