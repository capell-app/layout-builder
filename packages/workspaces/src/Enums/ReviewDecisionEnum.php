<?php

declare(strict_types=1);

namespace Capell\Workspaces\Enums;

enum ReviewDecisionEnum: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
}
