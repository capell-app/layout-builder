<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Enums;

enum AssetSlot: string
{
    case Base = 'base';
    case Head = 'head';
    case AboveFold = 'above_fold';
    case BelowFold = 'below_fold';
    case Interactive = 'interactive';
}
