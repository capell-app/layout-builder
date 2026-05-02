<?php

declare(strict_types=1);

namespace Capell\Workspaces\Checks;

enum PublishCheckSeverity: string
{
    case Info = 'info';
    case Warn = 'warn';
    case Error = 'error';
}
