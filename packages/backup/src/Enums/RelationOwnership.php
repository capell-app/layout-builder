<?php

declare(strict_types=1);

namespace Capell\Backup\Enums;

enum RelationOwnership: string
{
    case Owned = 'owned';
    case Shared = 'shared';
}
