<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Enums;

use Filament\Support\Contracts\HasLabel;

enum EmailMessageStatus: string implements HasLabel
{
    case Requested = 'requested';
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
    case PartiallyFailed = 'partially_failed';

    public function getLabel(): string
    {
        return __("capell-email-studio::generic.statuses.message.{$this->value}");
    }
}
