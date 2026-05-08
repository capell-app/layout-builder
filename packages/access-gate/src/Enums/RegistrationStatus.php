<?php

declare(strict_types=1);

namespace Capell\AccessGate\Enums;

enum RegistrationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Claimed = 'claimed';
    case Expired = 'expired';
}
