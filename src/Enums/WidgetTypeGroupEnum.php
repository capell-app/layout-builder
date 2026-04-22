<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

enum WidgetTypeGroupEnum: string
{
    case Asset = 'asset';

    case Content = 'content';

    case Media = 'media';

    case Page = 'page';

    case System = 'system';
}
