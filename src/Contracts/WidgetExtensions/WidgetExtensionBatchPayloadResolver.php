<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts\WidgetExtensions;

use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionPayloadBatchData;
use Spatie\LaravelData\Data;

interface WidgetExtensionBatchPayloadResolver
{
    /**
     * Resolve public payloads for a batch keyed by opaque widget instance ID.
     *
     * @return array<string, Data>
     */
    public function resolve(WidgetExtensionPayloadBatchData $batch): array;
}
