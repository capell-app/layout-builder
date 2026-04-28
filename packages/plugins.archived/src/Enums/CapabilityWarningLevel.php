<?php

declare(strict_types=1);

namespace Capell\Plugins\Enums;

enum CapabilityWarningLevel: string
{
    case Green = 'green';
    case Yellow = 'yellow';
    case Red = 'red';
}
