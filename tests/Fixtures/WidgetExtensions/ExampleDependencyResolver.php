<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions;

use Capell\LayoutBuilder\Contracts\WidgetExtensions\WidgetExtensionDependencyResolver;
use Spatie\LaravelData\Data;

final class ExampleDependencyResolver implements WidgetExtensionDependencyResolver
{
    public function resolve(Data $input): array
    {
        return ['media:123'];
    }
}
