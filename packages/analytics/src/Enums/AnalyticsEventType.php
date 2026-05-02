<?php

declare(strict_types=1);

namespace Capell\Analytics\Enums;

use Filament\Support\Contracts\HasLabel;

enum AnalyticsEventType: string implements HasLabel
{
    case PageView = 'page_view';
    case Click = 'click';
    case Form = 'form';
    case Custom = 'custom';
    case Consent = 'consent';

    public function getLabel(): string
    {
        return __('capell-analytics::settings.event_types.' . $this->value);
    }
}
