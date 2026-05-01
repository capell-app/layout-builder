<?php

declare(strict_types=1);

namespace Capell\Campaigns\Enums;

use Filament\Support\Contracts\HasLabel;

enum ConversionGoalType: string implements HasLabel
{
    case PageView = 'page_view';
    case CtaClick = 'cta_click';
    case FormSubmission = 'form_submission';
    case CustomAction = 'custom_action';

    public function getLabel(): string
    {
        return __('capell-campaigns::generic.goal_types.' . $this->value);
    }
}
