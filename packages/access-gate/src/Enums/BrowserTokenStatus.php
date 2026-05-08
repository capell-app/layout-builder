<?php

declare(strict_types=1);

namespace Capell\AccessGate\Enums;

enum BrowserTokenStatus: string
{
    case Active = 'active';
    case Revoked = 'revoked';
    case Expired = 'expired';
}
