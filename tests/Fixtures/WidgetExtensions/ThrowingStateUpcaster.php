<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions;

use Capell\LayoutBuilder\Contracts\WidgetExtensions\WidgetExtensionStateUpcaster;
use RuntimeException;

final class ThrowingStateUpcaster implements WidgetExtensionStateUpcaster
{
    public function upcast(array $state, int $fromVersion, int $toVersion): array
    {
        throw new RuntimeException('Sensitive fixture detail must not be logged.');
    }
}
