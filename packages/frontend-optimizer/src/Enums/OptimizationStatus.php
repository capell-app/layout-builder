<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Enums;

enum OptimizationStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Generated = 'generated';
    case Failed = 'failed';
}
