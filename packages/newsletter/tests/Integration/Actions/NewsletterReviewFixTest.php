<?php

declare(strict_types=1);

use Capell\Newsletter\Actions\ExportSubscribersAction;
use Capell\Newsletter\Actions\ImportSubscribersAction;
use Capell\Newsletter\Actions\RequeueDueProviderSyncAttemptsAction;
use Capell\Newsletter\Actions\SyncSubscriberToProviderAction;
use Capell\Newsletter\Actions\UpsertSubscriberAction;
use Capell\Newsletter\Data\ConsentEvidenceData;
use Capell\Newsletter\Data\SubscriberData;
use Capell\Newsletter\Enums\AuthType;
use Capell\Newsletter\Enums\ConsentEventType;
use Capell\Newsletter\Enums\ProviderType;
use Capell\Newsletter\Enums\ResourceEnum;
use Capell\Newsletter\Enums\ResubscribePolicy;
use Capell\Newsletter\Enums\SubscriberStatus;
use Capell\Newsletter\Enums\SyncStatus;
use Capell\Newsletter\Filament\Resources\FormMappings\FormMappingResource;
use Capell\Newsletter\Filament\Resources\ImportBatches\ImportBatchResource;
use Capell\Newsletter\Filament\Resources\NewsletterTags\NewsletterTagResource;
use Capell\Newsletter\Filament\Resources\ProviderAudiences\ProviderAudienceResource;
use Capell\Newsletter\Filament\Resources\ProviderConnections\ProviderConnectionResource;
use Capell\Newsletter\Filament\Resources\ProviderInterestMappings\ProviderInterestMappingResource;
use Capell\Newsletter\Filament\Resources\Segments\SegmentResource;
use Capell\Newsletter\Filament\Resources\Subscribers\SubscriberResource;
use Capell\Newsletter\Filament\Resources\SyncAttempts\SyncAttemptResource;
use Capell\Newsletter\Models\ConsentEvent;
use Capell\Newsletter\Models\ProviderAudience;
use Capell\Newsletter\Models\ProviderConnection;
use Capell\Newsletter\Models\Subscriber;
use Capell\Newsletter\Models\SyncAttempt;
use Capell\Newsletter\Support\NewsletterSettingsResolver;
use Illuminate\Support\Facades\Http;

it('fails closed for production provider webhooks without a valid secret', function (): void {
    $site = $this->createNewsletterSite();
    $connection = ProviderConnection::query()->create([
        'site_id' => $site->getKey(),
        'name' => 'Mailchimp',
        'provider' => ProviderType::Mailchimp,
        'auth_type' => AuthType::ApiKey,
        'credentials' => ['api_key' => 'test-us1'],
        'is_enabled' => true,
    ]);

    $payload = [
        'type' => 'unsubscribe',
        'data' => ['email' => 'webhook@example.com', 'id' => 'remote-1'],
    ];

    $this->postJson(route('capell-newsletter.provider-webhook', ['providerConnection' => $connection]), $payload)
        ->assertForbidden();

    $connection->forceFill(['webhook_secret' => 'valid-secret'])->save();

    $this->postJson(route('capell-newsletter.provider-webhook', ['providerConnection' => $connection]), $payload)
        ->assertForbidden();

    $this->postJson(route('capell-newsletter.provider-webhook', [
        'providerConnection' => $connection,
        'secret' => 'valid-secret',
    ]), $payload)->assertOk();

    expect(Subscriber::query()->forEmail($site->getKey(), 'webhook@example.com')->first()?->status)
        ->toBe(SubscriberStatus::Unsubscribed);
});

it('requeues due retry scheduled sync attempts', function (): void {
    config()->set('queue.default', 'null');

    $site = $this->createNewsletterSite();
    $subscriber = Subscriber::factory()->create([
        'site_id' => $site->getKey(),
        'email' => 'retry@example.com',
    ]);
    $connection = ProviderConnection::query()->create([
        'site_id' => $site->getKey(),
        'name' => 'Mailchimp',
        'provider' => ProviderType::Fake,
        'auth_type' => AuthType::ApiKey,
        'credentials' => ['api_key' => 'test-us1'],
        'is_enabled' => true,
    ]);
    $audience = ProviderAudience::query()->create([
        'provider_connection_id' => $connection->getKey(),
        'name' => 'Main',
        'remote_id' => 'audience-1',
        'is_default' => true,
        'sync_subscribed_only' => true,
    ]);
    $dueAttempt = SyncAttempt::query()->create([
        'subscriber_id' => $subscriber->getKey(),
        'provider_connection_id' => $connection->getKey(),
        'provider_audience_id' => $audience->getKey(),
        'operation' => 'sync_subscriber',
        'sync_status' => SyncStatus::RetryScheduled,
        'attempts' => 1,
        'next_retry_at' => now()->subMinute(),
    ]);
    $futureAttempt = SyncAttempt::query()->create([
        'subscriber_id' => $subscriber->getKey(),
        'provider_connection_id' => $connection->getKey(),
        'provider_audience_id' => $audience->getKey(),
        'operation' => 'sync_subscriber',
        'sync_status' => SyncStatus::RetryScheduled,
        'attempts' => 1,
        'next_retry_at' => now()->addHour(),
    ]);
    expect($dueAttempt->exists)->toBeTrue()
        ->and($futureAttempt->exists)->toBeTrue();

    expect(RequeueDueProviderSyncAttemptsAction::run(dispatchJobs: false))->toBe(1)
        ->and($dueAttempt->refresh()->sync_status)->toBe(SyncStatus::Pending)
        ->and($futureAttempt->refresh()->sync_status)->toBe(SyncStatus::RetryScheduled);
});

it('marks provider failures retryable before final failure', function (): void {
    Http::fake(['*' => Http::response(['error' => 'down'], 500)]);

    $site = $this->createNewsletterSite();
    $subscriber = Subscriber::factory()->create(['site_id' => $site->getKey()]);
    $connection = ProviderConnection::query()->create([
        'site_id' => $site->getKey(),
        'name' => 'Mailchimp',
        'provider' => ProviderType::Mailchimp,
        'auth_type' => AuthType::ApiKey,
        'credentials' => ['api_key' => 'test-us1'],
        'is_enabled' => true,
    ]);
    $audience = ProviderAudience::query()->create([
        'provider_connection_id' => $connection->getKey(),
        'name' => 'Main',
        'remote_id' => 'audience-1',
        'is_default' => true,
        'sync_subscribed_only' => true,
    ]);
    $syncAttempt = SyncAttempt::query()->create([
        'subscriber_id' => $subscriber->getKey(),
        'provider_connection_id' => $connection->getKey(),
        'provider_audience_id' => $audience->getKey(),
        'operation' => 'sync_subscriber',
        'sync_status' => SyncStatus::Pending,
        'attempts' => 0,
    ]);

    SyncSubscriberToProviderAction::run($syncAttempt);

    expect($syncAttempt->refresh()->sync_status)->toBe(SyncStatus::RetryScheduled)
        ->and($syncAttempt->next_retry_at)->not->toBeNull();

    config()->set('capell-newsletter.sync.retry_minutes', []);

    SyncSubscriberToProviderAction::run($syncAttempt);

    expect($syncAttempt->refresh()->sync_status)->toBe(SyncStatus::Failed);
});

it('registers the planned newsletter admin resources', function (): void {
    expect(array_map(static fn (ResourceEnum $resource): string => $resource->value, ResourceEnum::cases()))
        ->toContain(
            SubscriberResource::class,
            ProviderConnectionResource::class,
            ProviderAudienceResource::class,
            ProviderInterestMappingResource::class,
            FormMappingResource::class,
            NewsletterTagResource::class,
            SegmentResource::class,
            ImportBatchResource::class,
            SyncAttemptResource::class,
        );
});

it('records admin updates through the subscriber lifecycle action', function (): void {
    $site = $this->createNewsletterSite();

    $subscriber = UpsertSubscriberAction::run(new SubscriberData(
        siteId: (int) $site->getKey(),
        email: 'admin@example.com',
        status: SubscriberStatus::Subscribed,
        firstName: 'Ada',
    ), new ConsentEvidenceData(sourceType: 'admin'), ConsentEventType::AdminUpdated);

    expect($subscriber)->toBeInstanceOf(Subscriber::class)
        ->and(ConsentEvent::query()->where('subscriber_id', $subscriber->getKey())->where('event_type', ConsentEventType::AdminUpdated)->exists())->toBeTrue();
});

it('validates CSV imports and exports safe fields', function (): void {
    $site = $this->createNewsletterSite();
    $batch = ImportSubscribersAction::run((int) $site->getKey(), [
        ['email' => 'valid@example.com', 'first_name' => 'Val'],
        ['email' => 'valid@example.com', 'first_name' => 'Duplicate'],
        ['email' => 'not-an-email'],
    ], 'Imported from legacy CRM', dryRun: true);

    expect($batch->valid_rows)->toBe(1)
        ->and($batch->invalid_rows)->toBe(2)
        ->and(Subscriber::query()->where('site_id', $site->getKey())->count())->toBe(0);

    ImportSubscribersAction::run((int) $site->getKey(), [
        ['email' => 'valid@example.com', 'first_name' => 'Val'],
    ], 'Imported from legacy CRM', dryRun: false);

    expect(ExportSubscribersAction::run((int) $site->getKey())->first())
        ->toHaveKeys(['email', 'first_name', 'last_name', 'status', 'subscribed_at', 'unsubscribed_at'])
        ->not->toHaveKeys(['email_hash', 'profile']);
});

it('resolves resubscribe policy per site', function (): void {
    $firstSite = $this->createNewsletterSite('First');
    $secondSite = $this->createNewsletterSite('Second');

    app()->instance(NewsletterSettingsResolver::class, new class($firstSite->getKey()) extends NewsletterSettingsResolver
    {
        public function __construct(
            private readonly int|string $firstSiteId,
        ) {}

        public function resubscribePolicyForSite(int $siteId): ResubscribePolicy
        {
            if ($siteId === (int) $this->firstSiteId) {
                return ResubscribePolicy::AllowWithConsent;
            }

            return ResubscribePolicy::RequireDoubleOptIn;
        }
    });

    Subscriber::factory()->create([
        'site_id' => $firstSite->getKey(),
        'email' => 'resubscribe@example.com',
        'status' => SubscriberStatus::Unsubscribed,
    ]);
    Subscriber::factory()->create([
        'site_id' => $secondSite->getKey(),
        'email' => 'resubscribe@example.com',
        'status' => SubscriberStatus::Unsubscribed,
    ]);

    $firstSubscriber = UpsertSubscriberAction::run(new SubscriberData(
        siteId: (int) $firstSite->getKey(),
        email: 'resubscribe@example.com',
        status: SubscriberStatus::Subscribed,
    ), new ConsentEvidenceData(sourceType: 'form'));
    $secondSubscriber = UpsertSubscriberAction::run(new SubscriberData(
        siteId: (int) $secondSite->getKey(),
        email: 'resubscribe@example.com',
        status: SubscriberStatus::Subscribed,
    ), new ConsentEvidenceData(sourceType: 'form'));

    expect($firstSubscriber->status)->toBe(SubscriberStatus::Subscribed)
        ->and($secondSubscriber->status)->toBe(SubscriberStatus::Pending);
});
