<?php

declare(strict_types=1);

namespace Capell\Insights\Enums;

use Filament\Support\Contracts\HasLabel;

enum InsightsConsentRegion: string implements HasLabel
{
    case UkOrEurope = 'uk_or_europe';
    case OutsideUkOrEurope = 'outside_uk_or_europe';
    case Unknown = 'unknown';

    public function getLabel(): string
    {
        return __('capell-insights::consent.regions.' . $this->value);
    }
}
