<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Enums;

use Filament\Support\Contracts\HasLabel;

enum EmailProviderType: string implements HasLabel
{
    case Fake = 'fake';
    case Smtp = 'smtp';
    case Postmark = 'postmark';
    case Mailgun = 'mailgun';
    case Ses = 'ses';
    case Resend = 'resend';

    public function getLabel(): string
    {
        return __("capell-email-studio::generic.providers.{$this->value}");
    }
}
