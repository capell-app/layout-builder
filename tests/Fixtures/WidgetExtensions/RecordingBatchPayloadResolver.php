<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions;

use Capell\LayoutBuilder\Contracts\WidgetExtensions\WidgetExtensionBatchPayloadResolver;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionPayloadBatchData;
use RuntimeException;

final class RecordingBatchPayloadResolver implements WidgetExtensionBatchPayloadResolver
{
    public static int $calls = 0;

    public static string $mode = 'valid';

    public function resolve(WidgetExtensionPayloadBatchData $batch): array
    {
        self::$calls++;

        if (self::$mode === 'throw') {
            throw new RuntimeException('Sensitive resolver failure.');
        }

        $resolved = [];
        foreach ($batch->items as $item) {
            if (self::$mode === 'missing') {
                continue;
            }

            $resolved[$item->instanceId] = self::$mode === 'wrong'
                ? $item->input
                : new ExampleRenderData(title: $item->input->title);
        }

        return $resolved;
    }
}
