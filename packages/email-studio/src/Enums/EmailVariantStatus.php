<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Enums;

use Filament\Support\Contracts\HasLabel;

enum EmailVariantStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Active = 'active';
    case Retired = 'retired';

    public function getLabel(): string
    {
        return __("capell-email-studio::generic.statuses.variant.{$this->value}");
    }
}
