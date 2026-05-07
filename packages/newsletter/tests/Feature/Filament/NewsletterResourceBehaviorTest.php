<?php

declare(strict_types=1);

use Capell\Newsletter\Enums\AuthType;
use Capell\Newsletter\Enums\ImportBatchStatus;
use Capell\Newsletter\Enums\ImportBatchType;
use Capell\Newsletter\Enums\ProviderType;
use Capell\Newsletter\Enums\SegmentType;
use Capell\Newsletter\Enums\SubscriberStatus;
use Capell\Newsletter\Filament\Resources\ImportBatches\Pages\ListImportBatches;
use Capell\Newsletter\Filament\Resources\ProviderConnections\Pages\CreateProviderConnection;
use Capell\Newsletter\Filament\Resources\ProviderConnections\Pages\EditProviderConnection;
use Capell\Newsletter\Filament\Resources\ProviderConnections\Pages\ListProviderConnections;
use Capell\Newsletter\Filament\Resources\Segments\Pages\CreateSegment;
use Capell\Newsletter\Filament\Resources\Segments\Pages\EditSegment;
use Capell\Newsletter\Filament\Resources\Segments\Pages\ListSegments;
use Capell\Newsletter\Filament\Resources\Subscribers\Pages\CreateSubscriber;
use Capell\Newsletter\Filament\Resources\Subscribers\Pages\EditSubscriber;
use Capell\Newsletter\Filament\Resources\Subscribers\Pages\ListSubscribers;
use Capell\Newsletter\Models\ImportBatch;
use Capell\Newsletter\Models\ProviderConnection;
use Capell\Newsletter\Models\Segment;
use Capell\Newsletter\Models\Subscriber;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('renders the subscribers table with seeded records', function (): void {
    $site = $this->createNewsletterSite();
    $subscribers = Subscriber::factory()
        ->count(3)
        ->create(['site_id' => $site->getKey()]);

    livewire(ListSubscribers::class)
        ->assertSuccessful()
        ->assertCountTableRecords(3)
        ->assertCanSeeTableRecords($subscribers);
});

it('creates subscribers through the resource page', function (): void {
    $site = $this->createNewsletterSite();

    livewire(CreateSubscriber::class)
        ->assertSuccessful()
        ->fillForm([
            'site_id' => $site->getKey(),
            'email' => 'created@example.com',
            'first_name' => 'Created',
            'last_name' => 'Subscriber',
            'status' => SubscriberStatus::Subscribed->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $subscriber = Subscriber::query()->forEmail((int) $site->getKey(), 'created@example.com')->first();

    expect($subscriber)
        ->toBeInstanceOf(Subscriber::class)
        ->and($subscriber?->first_name)->toBe('Created')
        ->and($subscriber?->last_name)->toBe('Subscriber')
        ->and($subscriber?->status)->toBe(SubscriberStatus::Subscribed);
});

it('edits subscribers through the resource page', function (): void {
    $site = $this->createNewsletterSite();
    $subscriber = Subscriber::factory()->create([
        'site_id' => $site->getKey(),
        'email' => 'editable@example.com',
        'first_name' => 'Original',
        'last_name' => 'Name',
        'status' => SubscriberStatus::Pending,
    ]);

    livewire(EditSubscriber::class, [
        'record' => $subscriber->getRouteKey(),
    ])
        ->assertSuccessful()
        ->fillForm([
            'site_id' => $site->getKey(),
            'email' => 'editable@example.com',
            'first_name' => 'Updated',
            'last_name' => 'Subscriber',
            'status' => SubscriberStatus::Subscribed->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($subscriber->refresh())
        ->first_name->toBe('Updated')
        ->last_name->toBe('Subscriber')
        ->status->toBe(SubscriberStatus::Subscribed);
});

it('renders the segments table with seeded records', function (): void {
    $site = $this->createNewsletterSite();
    $segments = collect([
        Segment::query()->create([
            'site_id' => $site->getKey(),
            'name' => 'Subscribed',
            'handle' => 'subscribed',
            'type' => SegmentType::SavedFilter,
            'filters' => ['status' => SubscriberStatus::Subscribed->value],
            'is_active' => true,
        ]),
        Segment::query()->create([
            'site_id' => $site->getKey(),
            'name' => 'VIP',
            'handle' => 'vip',
            'type' => SegmentType::Static,
            'filters' => [],
            'is_active' => true,
        ]),
    ]);

    livewire(ListSegments::class)
        ->assertSuccessful()
        ->assertCountTableRecords(2)
        ->assertCanSeeTableRecords($segments);
});

it('creates and edits segments through resource pages', function (): void {
    $site = $this->createNewsletterSite();

    livewire(CreateSegment::class)
        ->assertSuccessful()
        ->fillForm([
            'site_id' => $site->getKey(),
            'name' => 'Recent signups',
            'handle' => 'recent-signups',
            'type' => SegmentType::SavedFilter->value,
            'filters' => ['status' => SubscriberStatus::Pending->value],
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $segment = Segment::query()
        ->where('site_id', $site->getKey())
        ->where('handle', 'recent-signups')
        ->firstOrFail();

    livewire(EditSegment::class, [
        'record' => $segment->getRouteKey(),
    ])
        ->assertSuccessful()
        ->fillForm([
            'site_id' => $site->getKey(),
            'name' => 'Subscribed signups',
            'handle' => 'subscribed-signups',
            'type' => SegmentType::SavedFilter->value,
            'filters' => ['status' => SubscriberStatus::Subscribed->value],
            'is_active' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($segment->refresh())
        ->name->toBe('Subscribed signups')
        ->handle->toBe('subscribed-signups')
        ->filters->toBe(['status' => SubscriberStatus::Subscribed->value])
        ->is_active->toBeFalse();
});

it('renders provider connections and persists create and edit resource submissions', function (): void {
    $site = $this->createNewsletterSite();
    $connection = ProviderConnection::query()->create([
        'site_id' => $site->getKey(),
        'name' => 'Mailchimp',
        'provider' => ProviderType::Mailchimp,
        'auth_type' => AuthType::ApiKey,
        'credentials' => ['api_key' => 'existing-us1'],
        'is_enabled' => true,
    ]);

    livewire(ListProviderConnections::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([$connection]);

    livewire(CreateProviderConnection::class)
        ->assertSuccessful()
        ->fillForm([
            'site_id' => $site->getKey(),
            'name' => 'Campaign Monitor',
            'provider' => ProviderType::CampaignMonitor->value,
            'auth_type' => AuthType::ApiKey->value,
            'credentials' => ['api_key' => 'created-key'],
            'is_enabled' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('newsletter_provider_connections', [
        'site_id' => $site->getKey(),
        'name' => 'Campaign Monitor',
        'provider' => ProviderType::CampaignMonitor->value,
        'auth_type' => AuthType::ApiKey->value,
        'is_enabled' => true,
    ]);

    livewire(EditProviderConnection::class, [
        'record' => $connection->getRouteKey(),
    ])
        ->assertSuccessful()
        ->fillForm([
            'site_id' => $site->getKey(),
            'name' => 'Mailchimp OAuth',
            'provider' => ProviderType::Mailchimp->value,
            'auth_type' => AuthType::OAuth->value,
            'credentials' => ['client_id' => 'updated-client'],
            'is_enabled' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($connection->refresh())
        ->name->toBe('Mailchimp OAuth')
        ->auth_type->toBe(AuthType::OAuth)
        ->credentials->toBe(['client_id' => 'updated-client'])
        ->is_enabled->toBeFalse();
});

it('renders import batches and creates batches through import actions', function (): void {
    $site = $this->createNewsletterSite();
    $batch = ImportBatch::query()->create([
        'site_id' => $site->getKey(),
        'type' => ImportBatchType::Import,
        'status' => ImportBatchStatus::DryRun,
        'filename' => 'seeded.csv',
        'consent_basis' => 'Imported with consent',
        'dry_run_payload' => [],
        'source_meta' => [],
        'total_rows' => 1,
        'valid_rows' => 1,
        'invalid_rows' => 0,
    ]);

    livewire(ListImportBatches::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([$batch])
        ->callAction('dry_run_import', data: [
            'site_id' => $site->getKey(),
            'csv' => null,
            'csv_contents' => "email,first_name\npreview@example.com,Preview\n",
            'consent_basis' => 'Imported from launch list',
            'tag_ids' => [],
        ])
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(2);

    expect(Subscriber::query()->forEmail((int) $site->getKey(), 'preview@example.com')->exists())->toBeFalse();

    livewire(ListImportBatches::class)
        ->assertSuccessful()
        ->callAction('commit_import', data: [
            'site_id' => $site->getKey(),
            'csv' => null,
            'csv_contents' => "email,first_name\ncommitted@example.com,Committed\n",
            'consent_basis' => 'Imported from launch list',
            'tag_ids' => [],
        ])
        ->assertHasNoFormErrors();

    $subscriber = Subscriber::query()->forEmail((int) $site->getKey(), 'committed@example.com')->first();

    expect($subscriber)
        ->toBeInstanceOf(Subscriber::class)
        ->and($subscriber?->first_name)->toBe('Committed')
        ->and(ImportBatch::query()->where('site_id', $site->getKey())->where('status', ImportBatchStatus::Completed)->exists())->toBeTrue();
});
