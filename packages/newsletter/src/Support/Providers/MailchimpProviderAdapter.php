<?php

declare(strict_types=1);

namespace Capell\Newsletter\Support\Providers;

use Capell\Newsletter\Contracts\NewsletterProviderAdapter;
use Capell\Newsletter\Data\ProviderAudienceData;
use Capell\Newsletter\Data\ProviderSubscriberData;
use Capell\Newsletter\Data\ProviderSyncResultData;
use Capell\Newsletter\Data\ProviderWebhookEventData;
use Capell\Newsletter\Enums\SubscriberStatus;
use Capell\Newsletter\Models\ProviderAudience;
use Capell\Newsletter\Models\ProviderConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MailchimpProviderAdapter implements NewsletterProviderAdapter
{
    public function supportsOAuth(): bool
    {
        return true;
    }

    public function supportsProviderOwnedConfirmation(): bool
    {
        return true;
    }

    public function listAudiences(ProviderConnection $connection): array
    {
        $response = Http::withBasicAuth('capell', $this->apiKey($connection))
            ->get($this->baseUrl($connection) . '/lists');

        if (! $response->successful()) {
            return [];
        }

        return collect($response->json('lists', []))
            ->filter(static fn (mixed $audience): bool => is_array($audience))
            ->map(static fn (array $audience): ProviderAudienceData => new ProviderAudienceData(
                remoteId: (string) ($audience['id'] ?? ''),
                name: (string) ($audience['name'] ?? ''),
                settings: $audience,
            ))
            ->filter(static fn (ProviderAudienceData $audience): bool => $audience->remoteId !== '')
            ->values()
            ->all();
    }

    public function syncSubscriber(
        ProviderConnection $connection,
        ProviderAudience $audience,
        ProviderSubscriberData $subscriber,
    ): ProviderSyncResultData {
        $payload = [
            'email_address' => $subscriber->email,
            'status_if_new' => $this->mailchimpStatus($subscriber->status),
            'status' => $this->mailchimpStatus($subscriber->status),
            'merge_fields' => array_filter([
                'FNAME' => $subscriber->firstName,
                'LNAME' => $subscriber->lastName,
                ...$subscriber->profile,
            ], static fn (mixed $value): bool => $value !== null && $value !== ''),
            'interests' => collect($subscriber->interests)
                ->mapWithKeys(static fn (mixed $interest): array => [$interest->remoteId => true])
                ->all(),
        ];

        $subscriberHash = md5(mb_strtolower($subscriber->email));
        $response = Http::withBasicAuth('capell', $this->apiKey($connection))
            ->put($this->baseUrl($connection) . '/lists/' . $audience->remote_id . '/members/' . $subscriberHash, $payload);

        return new ProviderSyncResultData(
            successful: $response->successful(),
            remoteId: $response->successful() ? $subscriberHash : null,
            remoteStatus: is_string($response->json('status')) ? $response->json('status') : null,
            errorMessage: $response->successful() ? null : $response->body(),
            payload: $payload,
        );
    }

    public function verifyWebhook(ProviderConnection $connection, Request $request): bool
    {
        $secret = $connection->webhook_secret;

        if (! is_string($secret) || $secret === '') {
            return false;
        }

        return hash_equals($secret, (string) $request->query('secret'));
    }

    public function normalizeWebhook(ProviderConnection $connection, Request $request): ?ProviderWebhookEventData
    {
        $email = $request->input('data.email') ?? $request->input('email');
        $type = (string) $request->input('type', 'profile');

        if (! is_string($email) || trim($email) === '') {
            return null;
        }

        return new ProviderWebhookEventData(
            email: $email,
            status: match ($type) {
                'unsubscribe' => SubscriberStatus::Unsubscribed,
                'cleaned' => SubscriberStatus::Bounced,
                'abuse' => SubscriberStatus::Complained,
                default => SubscriberStatus::Subscribed,
            },
            eventType: $type,
            remoteId: is_string($request->input('data.id')) ? $request->input('data.id') : null,
            payload: $request->all(),
        );
    }

    private function apiKey(ProviderConnection $connection): string
    {
        $credentials = is_array($connection->credentials) ? $connection->credentials : [];

        return $credentials['api_key'] ?? '';
    }

    private function baseUrl(ProviderConnection $connection): string
    {
        $apiKey = $this->apiKey($connection);
        $dataCenter = str_contains($apiKey, '-') ? str($apiKey)->afterLast('-')->toString() : 'us1';

        return sprintf('https://%s.api.mailchimp.com/3.0', $dataCenter);
    }

    private function mailchimpStatus(SubscriberStatus $status): string
    {
        return match ($status) {
            SubscriberStatus::Subscribed => 'subscribed',
            SubscriberStatus::Pending => 'pending',
            SubscriberStatus::Unsubscribed => 'unsubscribed',
            SubscriberStatus::Bounced, SubscriberStatus::Complained, SubscriberStatus::Suppressed => 'cleaned',
        };
    }
}
