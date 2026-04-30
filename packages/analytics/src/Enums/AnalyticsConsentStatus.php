<?php

declare(strict_types=1);

namespace Capell\Analytics\Enums;

use Filament\Support\Contracts\HasLabel;

enum AnalyticsConsentStatus: string implements HasLabel
{
    case Pending = 'pending';
    case AcceptedAll = 'accepted_all';
    case RejectedNonEssential = 'rejected_non_essential';
    case Granular = 'granular';

    public function getLabel(): string
    {
        return __("capell-analytics::consent.statuses.{$this->value}");
    }
}
