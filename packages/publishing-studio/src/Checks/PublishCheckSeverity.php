<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Checks;

enum PublishCheckSeverity: string
{
    case Info = 'info';
    case Warn = 'warn';
    case Error = 'error';
}
