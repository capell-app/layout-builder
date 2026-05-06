<?php

declare(strict_types=1);

namespace Capell\Insights\Enums;

use Filament\Support\Contracts\HasLabel;

enum InsightsConsentStatus: string implements HasLabel
{
    case Pending = 'pending';
    case AcceptedAll = 'accepted_all';
    case RejectedNonEssential = 'rejected_non_essential';
    case Granular = 'granular';

    public function getLabel(): string
    {
        return __('capell-insights::consent.statuses.' . $this->value);
    }
}
