<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum LayoutBulkChangeResultStatus: string
{
    case Changed = 'changed';
    case Unchanged = 'unchanged';
    case Skipped = 'skipped';
    case Blocked = 'blocked';
    case Applied = 'applied';
    case Drifted = 'drifted';
}
