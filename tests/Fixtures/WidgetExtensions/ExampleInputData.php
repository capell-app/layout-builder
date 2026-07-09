<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions;

use Spatie\LaravelData\Data;

final class ExampleInputData extends Data
{
    public function __construct(
        public string $title,
    ) {}
}
