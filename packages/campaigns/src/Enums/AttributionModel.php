<?php

declare(strict_types=1);

namespace Capell\Campaigns\Enums;

use Filament\Support\Contracts\HasLabel;

enum AttributionModel: string implements HasLabel
{
    case FirstTouch = 'first_touch';
    case LastTouch = 'last_touch';

    public function getLabel(): string
    {
        return __('capell-campaigns::generic.attribution_models.' . $this->value);
    }
}
