<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum LayoutBulkChangeRunStatus: string
{
    case Previewed = 'previewed';
    case Blocked = 'blocked';
    case Queued = 'queued';
    case Applying = 'applying';
    case Applied = 'applied';
    case PartiallyApplied = 'partially_applied';
    case Reverted = 'reverted';
    case PartiallyReverted = 'partially_reverted';
    case Failed = 'failed';
}
