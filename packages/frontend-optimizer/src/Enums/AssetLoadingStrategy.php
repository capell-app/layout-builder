<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Enums;

enum AssetLoadingStrategy: string
{
    case Critical = 'critical';
    case Blocking = 'blocking';
    case Preload = 'preload';
    case Deferred = 'deferred';
    case Lazy = 'lazy';
    case Interaction = 'interaction';
    case Idle = 'idle';
}
