<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Enums;

use Filament\Support\Contracts\HasLabel;

enum EmailEventType: string implements HasLabel
{
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Bounced = 'bounced';
    case Complained = 'complained';
    case Opened = 'opened';
    case Clicked = 'clicked';
    case Replied = 'replied';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return __("capell-email-studio::generic.events.{$this->value}");
    }
}
