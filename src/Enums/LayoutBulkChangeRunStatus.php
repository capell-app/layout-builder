<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum LayoutBulkChangeRunStatus: string
{
    case Previewed = 'previewed';
    case Blocked = 'blocked';
    case Applied = 'applied';
    case PartiallyApplied = 'partially_applied';
    case Failed = 'failed';
}
