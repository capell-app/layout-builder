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

    public static ?string $lastLanguageCode = null;

    public function resolve(WidgetExtensionPayloadBatchData $batch): array
    {
        self::$calls++;
        self::$lastLanguageCode = $batch->context->languageCode;

        if (self::$mode === 'throw') {
            throw new RuntimeException('Sensitive resolver failure.');
        }

        $resolved = [];
        foreach ($batch->items as $item) {
            if (self::$mode === 'missing') {
                continue;
            }

            if (! $item->input instanceof ExampleInputData) {
                throw new RuntimeException('Expected example widget input data.');
            }

            $resolved[$item->instanceId] = self::$mode === 'wrong'
                ? $item->input
                : new ExampleRenderData(title: $item->input->title);
        }

        return $resolved;
    }
}
