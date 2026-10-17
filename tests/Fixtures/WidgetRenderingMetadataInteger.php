<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

enum WidgetRenderingMetadataInteger: int
{
    case Component = 42;
    case Zero = 0;
}
