<?php

declare(strict_types=1);

namespace Capell\Migrator\Enums;

enum RelationOwnership: string
{
    case Owned = 'owned';
    case Shared = 'shared';
}
