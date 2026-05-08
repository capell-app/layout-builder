<?php

declare(strict_types=1);

use Capell\Newsletter\Data\ProviderInterestData;
use Capell\Newsletter\Data\ProviderSubscriberData;
use Capell\Newsletter\Enums\AuthType;
use Capell\Newsletter\Enums\ProviderType;
use Capell\Newsletter\Enums\SubscriberStatus;
use Capell\Newsletter\Models\ProviderAudience;
use Capell\Newsletter\Models\ProviderConnection;
use Capell\Newsletter\Support\Providers\CampaignMonitorProviderAdapter;
use Capell\Newsletter\Support\Providers\KitProviderAdapter;
use Capell\Newsletter\Support\Providers\MailchimpProviderAdapter;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

it('maps Mailchimp audiences, subscriber sync payloads, and webhook state', function (): void {
    Http::fake([
        'https://us21.api.mailchimp.com/3.0/lists' => Http::response([
            'lists' => [
                ['id' => 'audience-a', 'name' => 'Customers'],
                ['name' => 'Missing remote id'],
            ],
        ]),
        'https://us21.api.mailchimp.com/3.0/lists/audience-a/members/*' => Http::response([
            'status' => 'subscribed',
        ]),
    ]);
    $adapter = new MailchimpProviderAdapter;
    $connection = providerConnection(ProviderType::Mailchimp, [
        'credentials' => ['api_key' => 'abc-us21'],
        'webhook_secret' => 'mailchimp-secret',
    ]);
    $audience = providerAudience($connection, 'audience-a');

    $audiences = $adapter->listAudiences($connection);
    $result = $adapter->syncSubscriber($connection, $audience, providerSubscriber(
        status: SubscriberStatus::Subscribed,
        interests: [new ProviderInterestData(tagId: 10, remoteId: 'interest-a')],
    ));
    $webhook = $adapter->normalizeWebhook($connection, Request::create('/webhook?secret=mailchimp-secret', Symfony\Component\HttpFoundation\Request::METHOD_POST, [
        'type' => 'abuse',
        'data' => [
            'id' => 'remote-subscriber',
            'email' => 'reader@example.com',
        ],
    ]));

    expect($audiences)->toHaveCount(1)
        ->and($audiences[0]->remoteId)->toBe('audience-a')
        ->and($audiences[0]->name)->toBe('Customers')
        ->and($result->successful)->toBeTrue()
        ->and($result->remoteId)->toBe(md5('reader@example.com'))
        ->and($result->payload['merge_fields'])->toBe([
            'FNAME' => 'Ada',
            'LNAME' => 'Lovelace',
            'plan' => 'pro',
        ])
        ->and($result->payload['interests'])->toBe(['interest-a' => true])
        ->and($adapter->verifyWebhook($connection, Request::create('/webhook?secret=mailchimp-secret')))->toBeTrue()
        ->and($adapter->verifyWebhook($connection, Request::create('/webhook?secret=wrong')))->toBeFalse()
        ->and($webhook?->status)->toBe(SubscriberStatus::Complained)
        ->and($webhook?->remoteId)->toBe('remote-subscriber');

    Http::assertSent(static fn (ClientRequest $request): bool => $request->method() === 'PUT'
        && str_contains($request->url(), '/lists/audience-a/members/')
        && $request['status'] === 'subscribed');
});

it('maps Kit audiences, subscriber sync payloads, tag syncs, and webhook state', function (): void {
    Http::fake([
        'https://api.kit.com/v4/forms' => Http::response([
            'forms' => [
                ['id' => 123, 'name' => 'Newsletter'],
                ['name' => 'Missing remote id'],
            ],
        ]),
        'https://api.kit.com/v4/forms/123/subscribers' => Http::response([
            'subscriber' => ['id' => 'kit-subscriber'],
        ]),
        'https://api.kit.com/v4/tags/tag-a/subscribers' => Http::response([], 202),
    ]);
    $adapter = new KitProviderAdapter;
    $connection = providerConnection(ProviderType::Kit, [
        'oauth_tokens' => ['access_token' => 'kit-oauth-token'],
        'webhook_secret' => 'kit-secret',
    ]);
    $audience = providerAudience($connection, '123');

    $audiences = $adapter->listAudiences($connection);
    $result = $adapter->syncSubscriber($connection, $audience, providerSubscriber(
        status: SubscriberStatus::Pending,
        interests: [new ProviderInterestData(tagId: 10, remoteId: 'tag-a')],
    ));
    $webhookRequest = Request::create('/webhook', Symfony\Component\HttpFoundation\Request::METHOD_POST, [
        'event' => 'subscriber.unsubscribe',
        'subscriber' => [
            'id' => 'kit-subscriber',
            'email_address' => 'reader@example.com',
        ],
    ]);
    $webhookRequest->headers->set('X-Kit-Webhook-Secret', 'kit-secret');

    $webhook = $adapter->normalizeWebhook($connection, $webhookRequest);

    expect($audiences)->toHaveCount(1)
        ->and($audiences[0]->remoteId)->toBe('123')
        ->and($result->successful)->toBeTrue()
        ->and($result->remoteId)->toBe('kit-subscriber')
        ->and($result->payload['fields'])->toBe([
            'last_name' => 'Lovelace',
            'plan' => 'pro',
        ])
        ->and($adapter->verifyWebhook($connection, $webhookRequest))->toBeTrue()
        ->and($webhook?->status)->toBe(SubscriberStatus::Unsubscribed)
        ->and($webhook?->remoteId)->toBe('kit-subscriber');

    Http::assertSent(static fn (ClientRequest $request): bool => $request->url() === 'https://api.kit.com/v4/tags/tag-a/subscribers'
        && $request['email_address'] === 'reader@example.com');
});

it('maps Campaign Monitor audiences, subscriber sync payloads, and webhook state', function (): void {
    Http::fake([
        'https://api.createsend.com/api/v3.3/clients/client-a/lists.json' => Http::response([
            ['ListID' => 'list-a', 'Name' => 'Customers'],
            ['Name' => 'Missing remote id'],
        ]),
        'https://api.createsend.com/api/v3.3/subscribers/list-a.json' => Http::response([], 201),
    ]);
    $adapter = new CampaignMonitorProviderAdapter;
    $connection = providerConnection(ProviderType::CampaignMonitor, [
        'credentials' => [
            'api_key' => 'campaign-monitor-key',
            'client_id' => 'client-a',
        ],
        'webhook_secret' => 'campaign-secret',
    ]);
    $audience = providerAudience($connection, 'list-a');

    $audiences = $adapter->listAudiences($connection);
    $result = $adapter->syncSubscriber($connection, $audience, providerSubscriber());
    $webhook = $adapter->normalizeWebhook($connection, Request::create('/webhook?secret=campaign-secret', Symfony\Component\HttpFoundation\Request::METHOD_POST, [
        'Events' => [[
            'EmailAddress' => 'reader@example.com',
            'Type' => 'Bounce',
        ]],
    ]));

    expect($audiences)->toHaveCount(1)
        ->and($audiences[0]->remoteId)->toBe('list-a')
        ->and($result->successful)->toBeTrue()
        ->and($result->remoteId)->toBe('reader@example.com')
        ->and($result->payload['Name'])->toBe('Ada Lovelace')
        ->and($result->payload['CustomFields'])->toContain([
            'Key' => 'plan',
            'Value' => 'pro',
        ])
        ->and($adapter->verifyWebhook($connection, Request::create('/webhook?secret=campaign-secret')))->toBeTrue()
        ->and($webhook?->status)->toBe(SubscriberStatus::Bounced)
        ->and($webhook?->remoteId)->toBe('reader@example.com');
});

/**
 * @param  array<string, mixed>  $attributes
 */
function providerConnection(ProviderType $provider, array $attributes = []): ProviderConnection
{
    return ProviderConnection::query()->create([
        'site_id' => test()->createNewsletterSite()->getKey(),
        'name' => $provider->value,
        'provider' => $provider,
        'auth_type' => AuthType::ApiKey,
        'credentials' => [],
        'oauth_tokens' => [],
        'webhook_secret' => null,
        'is_enabled' => true,
        ...$attributes,
    ]);
}

function providerAudience(ProviderConnection $connection, string $remoteId): ProviderAudience
{
    return ProviderAudience::query()->create([
        'provider_connection_id' => $connection->getKey(),
        'name' => 'Default',
        'remote_id' => $remoteId,
        'is_default' => true,
        'sync_subscribed_only' => false,
    ]);
}

/**
 * @param  array<int, ProviderInterestData>  $interests
 */
function providerSubscriber(
    SubscriberStatus $status = SubscriberStatus::Subscribed,
    array $interests = [],
): ProviderSubscriberData {
    return new ProviderSubscriberData(
        email: 'reader@example.com',
        status: $status,
        firstName: 'Ada',
        lastName: 'Lovelace',
        profile: ['plan' => 'pro'],
        interests: $interests,
    );
}
