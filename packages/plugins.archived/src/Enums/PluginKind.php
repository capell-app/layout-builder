<?php

declare(strict_types=1);

namespace Capell\Plugins\Enums;

enum PluginKind: string
{
    case Theme = 'theme';
    case PageType = 'page_type';
    case Widget = 'widget';
    case Integration = 'integration';
    case ContentType = 'content_type';
    case Full = 'full';
}
