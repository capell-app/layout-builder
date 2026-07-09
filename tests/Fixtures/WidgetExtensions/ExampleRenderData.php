<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions;

use Spatie\LaravelData\Data;

final class ExampleRenderData extends Data
{
    public function __construct(
        public string $title,
    ) {}
}
