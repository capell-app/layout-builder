<?php

declare(strict_types=1);

namespace Capell\AccessGate\Enums;

enum IdentityMode: string
{
    case GuestLink = 'guest_link';
    case Authenticated = 'authenticated';
    case Hybrid = 'hybrid';
}
