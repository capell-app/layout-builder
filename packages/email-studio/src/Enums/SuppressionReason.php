<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Enums;

use Filament\Support\Contracts\HasLabel;

enum SuppressionReason: string implements HasLabel
{
    case Bounce = 'bounce';
    case Complaint = 'complaint';
    case Unsubscribe = 'unsubscribe';
    case Manual = 'manual';
    case Provider = 'provider';

    public function getLabel(): string
    {
        return __("capell-email-studio::generic.suppression_reasons.{$this->value}");
    }
}
