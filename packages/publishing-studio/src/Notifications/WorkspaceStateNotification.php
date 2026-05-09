<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Notifications;

use Capell\PublishingStudio\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

/**
 * Sent on every workspace state transition (submit, approve, reject,
 * publish, abandon) to the role recipients configured in
 * `capell.publishing-studio.notifications.recipients`.
 */
class WorkspaceStateNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Workspace $workspace,
        private readonly string $transition,
        private readonly ?Authenticatable $actor = null,
        private readonly ?string $notes = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        $channels = config('capell.publishing-studio.notifications.channels', ['mail']);

        return is_array($channels) ? array_values($channels) : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $actorName = $this->resolveActorName();
        $workspaceName = $this->workspace->name;
        $editUrl = $this->resolveEditUrl();

        $message = (new MailMessage)
            ->subject($this->translateMailLine($this->transition . '_subject', [
                'workspace' => $workspaceName,
            ]))
            ->line($this->translateMailLine($this->transition . '_intro', [
                'workspace' => $workspaceName,
                'actor' => $actorName,
            ]));

        if ($this->transition === 'submitted') {
            $message->level('warning');
        }

        if ($this->notes !== null && $this->notes !== '') {
            $message->line($this->translateMailLine('notes_prefix') . ' ' . $this->notes);
        }

        return $message->action($this->resolveCtaLabel(), $editUrl);
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'workspace_id' => $this->workspace->getKey(),
            'workspace_name' => $this->workspace->name,
            'transition' => $this->transition,
            'actor' => $this->actor?->getAuthIdentifier(),
            'notes' => $this->notes,
        ];
    }

    private function resolveCtaLabel(): string
    {
        return $this->translateMailLine($this->transition . '_cta');
    }

    /**
     * @param  array<string, mixed>  $replace
     */
    private function translateMailLine(string $line, array $replace = []): string
    {
        $key = 'capell-admin::workspace.mail.' . $line;
        $translated = __($key, $replace);

        if (is_string($translated) && $translated !== $key && ! str_contains($translated, '.mail.')) {
            return $translated;
        }

        $fallbackReplacements = [];

        foreach ($replace as $replacementKey => $value) {
            $fallbackReplacements[':' . $replacementKey] = is_scalar($value) ? (string) $value : '';
        }

        return strtr($this->fallbackMailLine($line), $fallbackReplacements);
    }

    private function fallbackMailLine(string $line): string
    {
        return match ($line) {
            'approved_cta' => 'Publish workspace',
            'published_cta' => 'View on live',
            'rejected_cta' => 'Edit workspace',
            'submitted_cta' => 'Review & Approve',
            'submitted_intro' => ':actor submitted workspace ":workspace" for review.',
            'submitted_subject' => 'Action required: :workspace needs your approval',
            'notes_prefix' => 'Notes:',
            default => 'Open workspace',
        };
    }

    private function resolveActorName(): string
    {
        if (! $this->actor instanceof Authenticatable) {
            return (string) __('capell-admin::workspace.mail.system_actor');
        }

        if ($this->actor instanceof Model) {
            $name = $this->actor->getAttribute('name');
            if (is_string($name) && $name !== '') {
                return $name;
            }

            $email = $this->actor->getAttribute('email');
            if (is_string($email) && $email !== '') {
                return $email;
            }
        }

        $identifier = $this->actor->getAuthIdentifier();

        return is_scalar($identifier) ? (string) $identifier : '';
    }

    private function resolveEditUrl(): string
    {
        try {
            return route('filament.admin.resources.publishing-studio.index');
        } catch (Throwable) {
            $url = config('app.url', '/');

            return is_string($url) ? $url : '/';
        }
    }
}
