<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum LayoutBulkWidgetOperationType: string
{
    case MoveWidget = 'move_widget';
    case RemoveWidget = 'remove_widget';
    case SwapWidgets = 'swap_widgets';
    case MoveWidgetToContainer = 'move_widget_to_container';
}
