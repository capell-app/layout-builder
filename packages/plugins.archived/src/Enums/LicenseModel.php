<?php

declare(strict_types=1);

namespace Capell\Plugins\Enums;

enum LicenseModel: string
{
    case Free = 'free';
    case PaidOneTime = 'paid_one_time';
    case PaidSubscription = 'paid_subscription';

    public function isPaid(): bool
    {
        return $this !== self::Free;
    }

    public function isSubscription(): bool
    {
        return $this === self::PaidSubscription;
    }
}
