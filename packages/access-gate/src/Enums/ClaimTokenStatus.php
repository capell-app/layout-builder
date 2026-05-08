<?php

declare(strict_types=1);

namespace Capell\AccessGate\Enums;

enum ClaimTokenStatus: string
{
    case Active = 'active';
    case Claimed = 'claimed';
    case Revoked = 'revoked';
    case Expired = 'expired';
}
