<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data\WidgetExtensions;

use Capell\Frontend\Data\FrontendRenderContextData;
use Spatie\LaravelData\Attributes\Hidden;
use Spatie\LaravelData\Data;

final class WidgetExtensionPayloadBatchData extends Data
{
    /** @param list<WidgetExtensionPayloadInputData> $items */
    public function __construct(
        public array $items,
        public WidgetExtensionRenderContextData $context,
        #[Hidden]
        public ?FrontendRenderContextData $frontendContext = null,
    ) {}
}
