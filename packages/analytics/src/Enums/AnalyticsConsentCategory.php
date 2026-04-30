<?php

declare(strict_types=1);

namespace Capell\Analytics\Enums;

use Filament\Support\Contracts\HasLabel;

enum AnalyticsConsentCategory: string implements HasLabel
{
    case Essential = 'essential';
    case Analytics = 'analytics';
    case Marketing = 'marketing';
    case Preferences = 'preferences';

    public function getLabel(): string
    {
        return __("capell-analytics::consent.categories.{$this->value}");
    }
}
