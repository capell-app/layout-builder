<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Enums;

enum ReviewDecisionEnum: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
}
