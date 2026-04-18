<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

enum WidgetTypeEnum: string
{
    case Assets = 'assets';

    case Sections = 'sections';

    case SectionBuilder = 'section-builder';

    case Default = 'default';

    case Hero = 'hero';

    case Media = 'media';

    case Navigation = 'navigation';

    case PageContents = 'page-content';

    case Results = 'results';

    case Pages = 'pages';

    case System = 'system';
}
