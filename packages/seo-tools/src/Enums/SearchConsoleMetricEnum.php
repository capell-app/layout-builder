<?php

declare(strict_types=1);

namespace Capell\SeoTools\Enums;

use Filament\Support\Contracts\HasLabel;

enum SearchConsoleMetricEnum: string implements HasLabel
{
    case Clicks = 'clicks';
    case Impressions = 'impressions';
    case Ctr = 'ctr';
    case Position = 'position';
    case SetupRequired = 'setup_required';

    public function getLabel(): string
    {
        return __('capell-seo-tools::generic.search_console_metric_' . $this->value);
    }
}
