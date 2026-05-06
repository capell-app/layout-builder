<?php

declare(strict_types=1);

namespace Capell\Insights\Enums;

use Filament\Support\Contracts\HasLabel;

enum InsightsConsentCategory: string implements HasLabel
{
    case Essential = 'essential';
    case Insights = 'insights';
    case Marketing = 'marketing';
    case Preferences = 'preferences';

    public function getLabel(): string
    {
        return __('capell-insights::consent.categories.' . $this->value);
    }
}
