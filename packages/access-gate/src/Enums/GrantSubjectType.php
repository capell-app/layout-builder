<?php

declare(strict_types=1);

namespace Capell\AccessGate\Enums;

enum GrantSubjectType: string
{
    case Email = 'email';
    case User = 'user';
}
