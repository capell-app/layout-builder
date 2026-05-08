<?php

declare(strict_types=1);

namespace Capell\Newsletter\Actions;

use Capell\Newsletter\Enums\SyncStatus;
use Capell\Newsletter\Jobs\SyncSubscriberToProviderJob;
use Capell\Newsletter\Models\ProviderAudience;
use Capell\Newsletter\Models\ProviderConnection;
use Capell\Newsletter\Models\Subscriber;
use Capell\Newsletter\Models\SyncAttempt;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsAction;

class QueueProviderSyncAction
{
    use AsAction;

    public function handle(Subscriber $subscriber, string $operation = 'sync_subscriber'): void
    {
        ProviderAudience::query()
            ->whereHas('providerConnection', function (Builder $query) use ($subscriber): void {
                $query
                    ->where('site_id', $subscriber->site_id)
                    ->where('is_enabled', true);
            })
            ->with(['providerConnection'])
            ->each(function (ProviderAudience $audience) use ($subscriber, $operation): void {
                $connection = $audience->providerConnection;

                if (! $connection instanceof ProviderConnection) {
                    return;
                }

                $syncAttempt = SyncAttempt::query()->create([
                    'subscriber_id' => $subscriber->getKey(),
                    'provider_connection_id' => $connection->getKey(),
                    'provider_audience_id' => $audience->getKey(),
                    'operation' => $operation,
                    'sync_status' => SyncStatus::Pending,
                    'payload_hash' => hash('sha256', implode('|', [
                        (string) $subscriber->getKey(),
                        $subscriber->status->value,
                        $subscriber->updated_at?->toISOString() ?? '',
                    ])),
                    'attempts' => 0,
                ]);

                dispatch(new SyncSubscriberToProviderJob($syncAttempt));
            });
    }
}
