<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions;

use Capell\LayoutBuilder\Contracts\WidgetExtensions\WidgetExtensionDependencyResolver;

final class ExampleDependencyResolver implements WidgetExtensionDependencyResolver
{
    public function resolve(array $state): array
    {
        return ['media:example'];
    }
}
