<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts\WidgetExtensions;

use Spatie\LaravelData\Data;

interface WidgetExtensionBatchPayloadResolver
{
    /**
     * Resolve public payloads for a batch keyed by opaque widget instance ID.
     *
     * @param  array<string, array<string, mixed>>  $widgetStates
     * @return array<string, Data>
     */
    public function resolve(array $widgetStates): array;
}
