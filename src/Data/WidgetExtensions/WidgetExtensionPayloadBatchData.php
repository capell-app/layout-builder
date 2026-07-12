<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data\WidgetExtensions;

use Spatie\LaravelData\Data;

final class WidgetExtensionPayloadBatchData extends Data
{
    /** @param list<WidgetExtensionPayloadInputData> $items */
    public function __construct(
        public array $items,
        public WidgetExtensionRenderContextData $context,
    ) {}
}
