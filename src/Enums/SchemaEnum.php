<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

enum SchemaEnum: string
{
    case Content = 'Content';
    case Layout = 'Layout';
    case LayoutContainer = 'LayoutContainer';
    case LayoutWidget = 'LayoutWidget';
    case Widget = 'Widget';
    case WidgetAsset = 'WidgetAsset';
}
