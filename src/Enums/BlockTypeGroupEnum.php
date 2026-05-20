<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum BlockTypeGroupEnum: string
{
    case Asset = 'asset';

    case Content = 'content';

    case Media = 'media';

    case Page = 'page';

    case System = 'system';
}
