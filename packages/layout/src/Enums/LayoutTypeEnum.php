<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

enum LayoutTypeEnum: string
{
    case Content = 'Content';
    case Layout = 'Layout';
    case Widget = 'Widget';
}
