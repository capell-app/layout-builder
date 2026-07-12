<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions;

use Capell\LayoutBuilder\Contracts\WidgetExtensions\WidgetExtensionStateUpcaster;

final class ExampleStateUpcaster implements WidgetExtensionStateUpcaster
{
    public function upcast(array $state, int $fromVersion, int $toVersion): array
    {
        return $state;
    }
}
