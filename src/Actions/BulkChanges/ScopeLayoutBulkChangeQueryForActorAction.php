<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\BulkChanges;

use Capell\Admin\Support\SiteScope;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;

final class ScopeLayoutBulkChangeQueryForActorAction
{
    use AsAction;

    /**
     * A null actor is reserved for the trusted CLI workflow. Web and queued
     * callers always provide an actor ID and fail closed if it cannot resolve.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function handle(Builder $query, ?int $actorId): Builder
    {
        if ($actorId === null) {
            return $query;
        }

        $actor = $this->actor($actorId);

        if (! $actor instanceof Authenticatable || SiteScope::isGlobalActor($actor)) {
            return $actor instanceof Authenticatable ? $query : $query->whereRaw('1 = 0');
        }

        if (! method_exists($actor, 'getAssignedSiteIds')) {
            return $query->whereRaw('1 = 0');
        }

        $assignedSiteIds = $actor->getAssignedSiteIds();

        if ($assignedSiteIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('site_id', $assignedSiteIds->map(static fn (mixed $siteId): int => (int) $siteId)->filter(static fn (int $siteId): bool => $siteId > 0)->all());
    }

    private function actor(int $actorId): ?Authenticatable
    {
        $userModel = config('auth.providers.users.model');

        if (! is_string($userModel) || ! is_a($userModel, Model::class, true)) {
            return null;
        }

        $actor = $userModel::query()->find($actorId);

        return $actor instanceof Authenticatable ? $actor : null;
    }
}
