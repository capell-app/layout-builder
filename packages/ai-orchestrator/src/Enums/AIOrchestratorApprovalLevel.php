<?php

declare(strict_types=1);

namespace Capell\AIOrchestrator\Enums;

enum AIOrchestratorApprovalLevel: string
{
    case None = 'none';
    case Draft = 'draft';
    case Developer = 'developer';
}
