<?php

declare(strict_types=1);

namespace Capell\Newsletter\Actions;

use Capell\Newsletter\Data\ConsentEvidenceData;
use Capell\Newsletter\Data\SubscriberData;
use Capell\Newsletter\Enums\ConsentEventType;
use Capell\Newsletter\Models\ProviderConnection;
use Capell\Newsletter\Models\Subscriber;
use Capell\Newsletter\Support\Providers\ProviderAdapterRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsAction;

class HandleProviderWebhookAction
{
    use AsAction;

    public function handle(ProviderConnection $connection, Request $request): ?Subscriber
    {
        $adapter = resolve(ProviderAdapterRegistry::class)->resolve($connection->provider);

        throw_unless($adapter->verifyWebhook($connection, $request), AuthorizationException::class, 'Invalid newsletter provider webhook signature.');

        $event = $adapter->normalizeWebhook($connection, $request);

        if ($event === null) {
            return null;
        }

        $subscriber = UpsertSubscriberAction::run(new SubscriberData(
            siteId: $connection->site_id,
            email: $event->email,
            status: $event->status,
        ), new ConsentEvidenceData(
            sourceType: 'provider_webhook',
            sourceId: $event->remoteId,
            extra: ['event_type' => $event->eventType],
        ), ConsentEventType::ProviderWebhook, false);

        RecordConsentEventAction::run(
            $subscriber,
            ConsentEventType::ProviderWebhook,
            new ConsentEvidenceData(
                sourceType: 'provider_webhook',
                sourceId: $event->remoteId,
                extra: ['event_type' => $event->eventType],
            ),
            $event->status,
            $connection,
            $event->payload,
        );

        return $subscriber;
    }
}
