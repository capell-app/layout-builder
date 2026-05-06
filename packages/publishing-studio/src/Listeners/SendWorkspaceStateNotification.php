<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Listeners;

use Capell\PublishingStudio\Events\WorkspaceStateChanged;
use Capell\PublishingStudio\Notifications\WorkspaceStateNotification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

/**
 * Routes {@see WorkspaceStateChanged} events to every user carrying one of
 * the recipient roles configured for that transition under
 * `capell.publishing-studio.notifications.recipients`. Recipients are deduplicated
 * across roles and the triggering actor is excluded so actors never get
 * notified about their own actions.
 */
class SendWorkspaceStateNotification
{
    public function handle(WorkspaceStateChanged $event): void
    {
        if (! config('capell.publishing-studio.notifications.enabled', true)) {
            return;
        }

        $recipientRoles = $this->rolesFor($event->transition);

        if ($recipientRoles === []) {
            return;
        }

        $notifiables = $this->resolveRecipients($recipientRoles, $event->actor);

        if ($notifiables === []) {
            return;
        }

        Notification::send(
            $notifiables,
            new WorkspaceStateNotification(
                $event->workspace,
                $event->transition,
                $event->actor,
                $event->notes,
            ),
        );
    }

    /** @return array<int, string> */
    private function rolesFor(string $transition): array
    {
        $roles = config(
            'capell.publishing-studio.notifications.recipients.' . $transition,
            [],
        );

        return is_array($roles) ? array_values(array_filter($roles, is_string(...))) : [];
    }

    /**
     * @param  array<int, string>  $roleNames
     * @return array<int, Authenticatable>
     */
    private function resolveRecipients(array $roleNames, ?Authenticatable $actor): array
    {
        $users = [];
        $actorKey = $actor?->getAuthIdentifier();

        foreach ($roleNames as $roleName) {
            $role = Role::query()->where('name', $roleName)->first();

            if (! $role instanceof Role) {
                continue;
            }

            foreach ($role->users as $user) {
                if (! $user instanceof Authenticatable) {
                    continue;
                }

                $identifier = $user->getAuthIdentifier();

                if ($actorKey !== null && $identifier === $actorKey) {
                    continue;
                }

                $users[(string) $identifier] = $user;
            }
        }

        return array_values($users);
    }
}
