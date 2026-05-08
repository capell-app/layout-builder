<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Listeners;

use Capell\PublishingStudio\Actions\InstallWorkspaceRolesAction;
use Capell\PublishingStudio\Enums\WorkspaceTransitionEnum;
use Capell\PublishingStudio\Events\WorkspaceStateChanged;
use Capell\PublishingStudio\Notifications\WorkspaceStateNotification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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
        if (config('capell.publishing-studio.notifications.enabled', true) !== true) {
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
            $this->defaultRolesFor($transition),
        );

        return is_array($roles) ? array_values(array_filter($roles, is_string(...))) : [];
    }

    /** @return array<int, string> */
    private function defaultRolesFor(string $transition): array
    {
        return match ($transition) {
            WorkspaceTransitionEnum::Submitted->value => [
                InstallWorkspaceRolesAction::ROLE_REVIEWER,
                InstallWorkspaceRolesAction::ROLE_RELEASE_MANAGER,
            ],
            WorkspaceTransitionEnum::Approved->value => [
                InstallWorkspaceRolesAction::ROLE_RELEASE_MANAGER,
            ],
            WorkspaceTransitionEnum::Published->value => [
                InstallWorkspaceRolesAction::ROLE_EDITOR,
                InstallWorkspaceRolesAction::ROLE_REVIEWER,
                InstallWorkspaceRolesAction::ROLE_RELEASE_MANAGER,
            ],
            WorkspaceTransitionEnum::Rejected->value,
            WorkspaceTransitionEnum::ChangesRequested->value => [
                InstallWorkspaceRolesAction::ROLE_EDITOR,
            ],
            default => [],
        };
    }

    /**
     * @param  array<int, string>  $roleNames
     * @return array<int, Authenticatable>
     */
    private function resolveRecipients(array $roleNames, ?Authenticatable $actor): array
    {
        $users = [];
        $actorKey = $actor?->getAuthIdentifier();
        $userModel = $this->resolveUserModel();

        if ($userModel === null) {
            return [];
        }

        $roles = Role::query()
            ->whereIn('name', $roleNames)
            ->get();

        if ($roles->isEmpty()) {
            return [];
        }

        $permissionRegistrar = resolve(PermissionRegistrar::class);
        $modelMorphKey = config('permission.column_names.model_morph_key');
        $tableName = config('permission.table_names.model_has_roles');

        if (! is_string($modelMorphKey) || ! is_string($tableName)) {
            return [];
        }

        $userIds = DB::table($tableName)
            ->whereIn($permissionRegistrar->pivotRole, $roles->pluck($roles->first()->getKeyName())->all())
            ->whereIn('model_type', $this->userMorphTypes($userModel))
            ->pluck($modelMorphKey)
            ->all();

        if ($userIds === []) {
            return [];
        }

        foreach ($userModel::query()->whereKey($userIds)->get() as $user) {
            if (! $user instanceof Authenticatable) {
                continue;
            }

            $identifier = $user->getAuthIdentifier();

            if ($actorKey !== null && $identifier === $actorKey) {
                continue;
            }

            $users[(string) $identifier] = $user;
        }

        return array_values($users);
    }

    /**
     * @return class-string<Model>|null
     */
    private function resolveUserModel(): ?string
    {
        $userModel = config('auth.providers.users.model');

        if (! is_string($userModel) || ! is_a($userModel, Model::class, true)) {
            return null;
        }

        return $userModel;
    }

    /**
     * @param  class-string<Model>  $userModel
     * @return array<int, string>
     */
    private function userMorphTypes(string $userModel): array
    {
        /** @var Model $model */
        $model = new $userModel;

        return array_values(array_unique([
            $userModel,
            $model->getMorphClass(),
        ]));
    }
}
