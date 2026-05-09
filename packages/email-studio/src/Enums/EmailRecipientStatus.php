<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Enums;

use Filament\Support\Contracts\HasLabel;

enum EmailRecipientStatus: string implements HasLabel
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Bounced = 'bounced';
    case Complained = 'complained';
    case Opened = 'opened';
    case Clicked = 'clicked';
    case Replied = 'replied';
    case Failed = 'failed';
    case Suppressed = 'suppressed';

    public function getLabel(): string
    {
        return __("capell-email-studio::generic.statuses.recipient.{$this->value}");
    }
}
