<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Enums;

use Filament\Support\Contracts\HasLabel;

enum CampaignStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Active = 'active';
    case Paused = 'paused';
    case Ended = 'ended';

    public function getLabel(): string
    {
        return __('capell-campaign-studio::generic.statuses.' . $this->value);
    }
}
