<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions;

use Capell\LayoutBuilder\Contracts\WidgetExtensions\WidgetExtensionDependencyResolver;
use Spatie\LaravelData\Data;

final class RecordingDependencyResolver implements WidgetExtensionDependencyResolver
{
    /** @var array<int, mixed> */
    public static array $identifiers = [];

    public function resolve(Data $input): array
    {
        /** @var list<string> $identifiers */
        $identifiers = self::$identifiers;

        return $identifiers;
    }
}
