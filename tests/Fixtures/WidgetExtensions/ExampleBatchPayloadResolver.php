<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions;

use Capell\LayoutBuilder\Contracts\WidgetExtensions\WidgetExtensionBatchPayloadResolver;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionPayloadBatchData;

final class ExampleBatchPayloadResolver implements WidgetExtensionBatchPayloadResolver
{
    public function resolve(WidgetExtensionPayloadBatchData $batch): array
    {
        return [];
    }
}
