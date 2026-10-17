<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

enum WidgetRenderingMetadataString: string
{
    case Component = '  rendered.component  ';
    case Blank = '   ';
}
