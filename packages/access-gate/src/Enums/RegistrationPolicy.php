<?php

declare(strict_types=1);

namespace Capell\AccessGate\Enums;

enum RegistrationPolicy: string
{
    case SinglePerEmail = 'single_per_email';
    case DuplicateAllowed = 'duplicate_allowed';
}
