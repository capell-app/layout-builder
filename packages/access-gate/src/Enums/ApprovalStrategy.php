<?php

declare(strict_types=1);

namespace Capell\AccessGate\Enums;

enum ApprovalStrategy: string
{
    case Manual = 'manual';
    case FirstNAutoApprove = 'first_n_auto_approve';
    case InviteOnly = 'invite_only';
    case AutoApprove = 'auto_approve';
}
