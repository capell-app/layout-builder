<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data\WidgetExtensions;

use Capell\Frontend\Data\FrontendRenderContextData;
use Spatie\LaravelData\Data;

final class WidgetExtensionRenderContextData extends Data
{
    public function __construct(
        public ?string $languageCode,
    ) {}

    public static function fromFrontendContext(FrontendRenderContextData $context): self
    {
        return new self(languageCode: $context->language?->code);
    }
}
