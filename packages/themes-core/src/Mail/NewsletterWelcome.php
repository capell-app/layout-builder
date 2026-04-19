<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterWelcome extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $siteName,
        public readonly ?string $subscriberName = null,
        public readonly ?string $unsubscribeUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('Welcome to %s', $this->siteName),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'capell::mail.newsletter-welcome',
            with: [
                'siteName' => $this->siteName,
                'subscriberName' => $this->subscriberName,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ],
        );
    }
}
