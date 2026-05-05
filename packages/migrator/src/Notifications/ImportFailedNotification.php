<?php

declare(strict_types=1);

namespace Capell\Migrator\Notifications;

use Capell\Migrator\Models\ImportSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

/**
 * Delivered when an {@see ImportSession} terminates in the Failed
 * state, carrying the recorded failure reason and a link to the
 * session detail page.
 */
class ImportFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly ImportSession $session,
        private readonly string $reason,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        $channels = config(
            'migrator.notifications.channels',
            ['mail', 'database'],
        );

        return is_array($channels) ? array_values(array_filter($channels, is_string(...))) : ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('capell-admin::exchanger.mail.failed_subject'))
            ->error()
            ->line(__('capell-admin::exchanger.mail.failed_intro'))
            ->line(__('capell-admin::exchanger.mail.failed_reason_prefix') . ' ' . $this->reason)
            ->action(__('capell-admin::exchanger.mail.failed_cta'), $this->resolveSessionUrl());
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'import_session_id' => $this->session->getKey(),
            'import_session_uuid' => $this->session->uuid,
            'failure_reason' => $this->reason,
            'outcome' => 'failed',
        ];
    }

    private function resolveSessionUrl(): string
    {
        try {
            return route('filament.admin.resources.import-sessions.view', [
                'record' => $this->session->getKey(),
            ]);
        } catch (Throwable) {
            $fallback = config('app.url', '/');

            return is_string($fallback) ? $fallback : '/';
        }
    }
}
