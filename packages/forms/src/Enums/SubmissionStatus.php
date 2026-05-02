<?php

declare(strict_types=1);

namespace Capell\Forms\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum SubmissionStatus: string implements HasColor, HasIcon, HasLabel
{
    case New = 'new';
    case Read = 'read';
    case Archived = 'archived';
    case Spam = 'spam';

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Read => 'gray',
            self::Archived => 'warning',
            self::Spam => 'danger',
        };
    }

    public function getIcon(): string|Heroicon
    {
        return match ($this) {
            self::New => Heroicon::OutlinedInbox,
            self::Read => Heroicon::OutlinedEnvelopeOpen,
            self::Archived => Heroicon::OutlinedArchiveBox,
            self::Spam => Heroicon::OutlinedNoSymbol,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::New => __('capell-forms::generic.submission_status.new'),
            self::Read => __('capell-forms::generic.submission_status.read'),
            self::Archived => __('capell-forms::generic.submission_status.archived'),
            self::Spam => __('capell-forms::generic.submission_status.spam'),
        };
    }
}
