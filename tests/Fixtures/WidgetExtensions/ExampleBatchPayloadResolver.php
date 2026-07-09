<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions;

use Capell\LayoutBuilder\Contracts\WidgetExtensions\WidgetExtensionBatchPayloadResolver;

final class ExampleBatchPayloadResolver implements WidgetExtensionBatchPayloadResolver
{
    public function resolve(array $widgetStates): array
    {
        return [];
    }
}
