<?php

declare(strict_types=1);

namespace Capell\Workspaces\Notifications;

use Capell\Workspaces\Models\Workspace;
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
 * `capell.workspaces.notifications.recipients`.
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
        $channels = config('capell.workspaces.notifications.channels', ['mail']);

        return is_array($channels) ? array_values($channels) : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $actorName = $this->resolveActorName();
        $workspaceName = $this->workspace->name;
        $editUrl = $this->resolveEditUrl();

        $message = (new MailMessage)
            ->subject(__('capell-admin::workspace.mail.' . $this->transition . '_subject', [
                'workspace' => $workspaceName,
            ]))
            ->line(__('capell-admin::workspace.mail.' . $this->transition . '_intro', [
                'workspace' => $workspaceName,
                'actor' => $actorName,
            ]));

        if ($this->transition === 'submitted') {
            $message->level('warning');
        }

        if ($this->notes !== null && $this->notes !== '') {
            $message->line(__('capell-admin::workspace.mail.notes_prefix') . ' ' . $this->notes);
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
        $key = 'capell-admin::workspace.mail.' . $this->transition . '_cta';
        $translated = __($key);

        if (is_string($translated) && $translated !== $key) {
            return $translated;
        }

        return (string) __('capell-admin::workspace.mail.cta');
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
            return route('filament.admin.resources.workspaces.index');
        } catch (Throwable) {
            $url = config('app.url', '/');

            return is_string($url) ? $url : '/';
        }
    }
}
