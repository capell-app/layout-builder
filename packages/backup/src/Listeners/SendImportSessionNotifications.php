<?php

declare(strict_types=1);

namespace Capell\Backup\Listeners;

use Capell\Backup\Events\ImportCompleted;
use Capell\Backup\Events\ImportFailed;
use Capell\Backup\Models\ImportSession;
use Capell\Backup\Notifications\ImportCompletedNotification;
use Capell\Backup\Notifications\ImportFailedNotification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Notifications\Notification as NotificationBase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

/**
 * Routes exchanger import lifecycle events to the initiating user
 * plus any "exchanger watcher" roles configured in
 * `backup.notifications.recipients`.
 */
class SendImportSessionNotifications
{
    public function handleCompleted(ImportCompleted $event): void
    {
        $this->dispatch(
            $event->session,
            new ImportCompletedNotification($event->session),
            'completed',
        );
    }

    public function handleFailed(ImportFailed $event): void
    {
        $this->dispatch(
            $event->session,
            new ImportFailedNotification($event->session, $event->reason),
            'failed',
        );
    }

    private function dispatch(ImportSession $session, NotificationBase $notification, string $outcome): void
    {
        if (! config('backup.notifications.enabled', true)) {
            return;
        }

        $recipients = $this->resolveRecipients($session, $outcome);

        if ($recipients === []) {
            return;
        }

        Notification::send($recipients, $notification);
    }

    /**
     * @return array<int, Authenticatable>
     */
    private function resolveRecipients(ImportSession $session, string $outcome): array
    {
        $users = [];

        $initiator = $this->resolveInitiator($session);
        if ($initiator instanceof Authenticatable) {
            $users[(string) $initiator->getAuthIdentifier()] = $initiator;
        }

        foreach ($this->rolesFor($outcome) as $roleName) {
            $role = Role::query()->where('name', $roleName)->first();

            if (! $role instanceof Role) {
                continue;
            }

            foreach ($role->users as $user) {
                if (! $user instanceof Authenticatable) {
                    continue;
                }

                $users[(string) $user->getAuthIdentifier()] = $user;
            }
        }

        return array_values($users);
    }

    private function resolveInitiator(ImportSession $session): ?Authenticatable
    {
        if ($session->user_id === null) {
            return null;
        }

        $modelClass = config('auth.providers.users.model');
        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            return null;
        }

        $user = $modelClass::query()->find($session->user_id);

        return $user instanceof Authenticatable ? $user : null;
    }

    /** @return array<int, string> */
    private function rolesFor(string $outcome): array
    {
        $roles = config(
            'backup.notifications.recipients.' . $outcome,
            [],
        );

        return is_array($roles) ? array_values(array_filter($roles, is_string(...))) : [];
    }
}
