<?php

declare(strict_types=1);

namespace Capell\Backup\Notifications;

use Capell\Backup\Models\ImportSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

/**
 * Delivered when an {@see ImportSession} finishes with a successful
 * report. Mail and database channels are driven from the
 * `backup.notifications.channels` config key so operators
 * opt in once.
 */
class ImportCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ImportSession $session) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        $channels = config(
            'backup.notifications.channels',
            ['mail', 'database'],
        );

        return is_array($channels) ? array_values(array_filter($channels, is_string(...))) : ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $counts = $this->resultCounts();

        return (new MailMessage)
            ->subject(__('capell-admin::exchanger.mail.completed_subject'))
            ->line(__('capell-admin::exchanger.mail.completed_intro', [
                'pages' => $counts['pages'],
            ]))
            ->action(__('capell-admin::exchanger.mail.completed_cta'), $this->resolveSessionUrl());
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'import_session_id' => $this->session->getKey(),
            'import_session_uuid' => $this->session->uuid,
            'result_summary' => $this->session->result_summary,
            'outcome' => 'completed',
        ];
    }

    /** @return array{pages: int} */
    private function resultCounts(): array
    {
        $summary = is_array($this->session->result_summary) ? $this->session->result_summary : [];
        $pages = $summary['pages_created'] ?? $summary['pages'] ?? 0;

        return ['pages' => is_int($pages) ? $pages : (int) $pages];
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
