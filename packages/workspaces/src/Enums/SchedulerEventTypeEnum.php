<?php

declare(strict_types=1);

namespace Capell\Workspaces\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SchedulerEventTypeEnum: string implements HasColor, HasLabel
{
    case Publish = 'publish';
    case Unpublish = 'unpublish';
    case Embargo = 'embargo';
    case ReviewReminder = 'review_reminder';

    public function getColor(): string
    {
        return match ($this) {
            self::Publish => 'success',
            self::Unpublish => 'danger',
            self::Embargo => 'warning',
            self::ReviewReminder => 'info',
        };
    }

    public function getLabel(): string
    {
        return (string) __('capell-workspaces::scheduler.event_types.' . $this->value);
    }
}
