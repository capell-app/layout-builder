<?php

declare(strict_types=1);

namespace Capell\Assistant\Enums;

enum AssistantApprovalLevel: string
{
    case None = 'none';
    case Draft = 'draft';
    case Developer = 'developer';
}
