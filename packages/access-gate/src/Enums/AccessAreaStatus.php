<?php

declare(strict_types=1);

namespace Capell\AccessGate\Enums;

enum AccessAreaStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Closed = 'closed';
}
