<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Enums;

use Filament\Support\Contracts\HasLabel;

enum EmailTemplateStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return __("capell-email-studio::generic.statuses.template.{$this->value}");
    }
}
