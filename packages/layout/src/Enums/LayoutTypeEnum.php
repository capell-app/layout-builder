<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

enum LayoutTypeEnum: string
{
    case Content = 'content';
    case Layout = 'layout';
    case Widget = 'widget';
}
