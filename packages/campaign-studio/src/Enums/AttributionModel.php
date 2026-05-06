<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Enums;

use Filament\Support\Contracts\HasLabel;

enum AttributionModel: string implements HasLabel
{
    case FirstTouch = 'first_touch';
    case LastTouch = 'last_touch';

    public function getLabel(): string
    {
        return __('capell-campaign-studio::generic.attribution_models.' . $this->value);
    }
}
