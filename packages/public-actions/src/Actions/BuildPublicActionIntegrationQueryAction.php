<?php

declare(strict_types=1);

namespace Capell\PublicActions\Actions;

use Capell\PublicActions\Enums\PublicActionIntegrationProvider;
use Capell\PublicActions\Enums\PublicActionStatus;
use Capell\PublicActions\Models\PublicAction;
use Capell\PublicActions\Models\PublicActionIntegrationToken;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildPublicActionIntegrationQueryAction
{
    use AsAction;

    /**
     * @return Builder<PublicAction>
     */
    public function handle(PublicActionIntegrationToken $token): Builder
    {
        return $this->apply(PublicAction::query(), $token);
    }

    /**
     * @param  Builder<PublicAction>  $query
     * @return Builder<PublicAction>
     */
    public function apply(Builder $query, PublicActionIntegrationToken $token): Builder
    {
        return $query
            ->where('status', PublicActionStatus::Active)
            ->when($token->site_id !== null, fn (Builder $query): Builder => $query
                ->where(fn (Builder $builder): Builder => $builder
                    ->where('site_id', $token->site_id)
                    ->orWhereNull('site_id'))
                ->orderByRaw('CASE WHEN site_id = ? THEN 0 ELSE 1 END', [$token->site_id]))
            ->where(fn (Builder $query): Builder => $query->where(
                'settings->' . $this->enabledSettingKey($token->provider),
                true,
            ));
    }

    private function enabledSettingKey(PublicActionIntegrationProvider $provider): string
    {
        return match ($provider) {
            PublicActionIntegrationProvider::Zapier => 'zapier_enabled',
            PublicActionIntegrationProvider::Api => 'api_enabled',
        };
    }
}
