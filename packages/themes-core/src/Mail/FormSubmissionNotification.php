<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FormSubmissionNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, scalar|null>  $fields
     */
    public function __construct(
        public readonly string $formName,
        public readonly array $fields,
        public readonly ?string $submittedFrom = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('New %s submission', $this->formName),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'capell::mail.form-submission',
            with: [
                'formName' => $this->formName,
                'fields' => $this->fields,
                'submittedFrom' => $this->submittedFrom,
            ],
        );
    }
}
