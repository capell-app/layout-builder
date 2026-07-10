<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data\WidgetExtensions;

use Spatie\LaravelData\Data;

final class WidgetExtensionPayloadInputData extends Data
{
    public function __construct(
        public string $instanceId,
        public Data $input,
    ) {}
}
