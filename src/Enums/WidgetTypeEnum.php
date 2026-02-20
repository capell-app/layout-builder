<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

enum WidgetTypeEnum: string
{
    case Assets = 'assets';

    case Contents = 'contents';

    case ContentBuilder = 'content-builder';

    case Default = 'default';

    case Media = 'media';

    case Navigation = 'navigation';

    case PageContents = 'page-content';

    case Results = 'results';

    case Pages = 'pages';

    case System = 'system';
}
