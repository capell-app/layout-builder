<?php

declare(strict_types=1);

use Capell\Newsletter\Actions\ApplyNewsletterTagsAction;
use Capell\Newsletter\Actions\EvaluateNewsletterSegmentAction;
use Capell\Newsletter\Enums\AuthType;
use Capell\Newsletter\Enums\ProviderType;
use Capell\Newsletter\Enums\SegmentType;
use Capell\Newsletter\Enums\SubscriberStatus;
use Capell\Newsletter\Models\ProviderAudience;
use Capell\Newsletter\Models\ProviderConnection;
use Capell\Newsletter\Models\ProviderSubscriber;
use Capell\Newsletter\Models\Segment;
use Capell\Newsletter\Models\Subscriber;
use Capell\Tags\Models\Tag;

it('includes subscribers attached to static segments', function (): void {
    $site = $this->createNewsletterSite();
    $matchingSubscriber = Subscriber::factory()->create(['site_id' => $site->getKey()]);
    $unattachedSubscriber = Subscriber::factory()->create(['site_id' => $site->getKey()]);
    $otherSiteSubscriber = Subscriber::factory()->create(['site_id' => $this->createNewsletterSite('Other')->getKey()]);
    $segment = Segment::query()->create([
        'site_id' => $site->getKey(),
        'name' => 'VIP subscribers',
        'handle' => 'vip-subscribers',
        'type' => SegmentType::Static,
        'filters' => [],
        'is_active' => true,
    ]);

    $segment->subscribers()->attach($matchingSubscriber);

    expect(segmentSubscriberIds($segment))->toBe([
        $matchingSubscriber->getKey(),
    ])
        ->not->toContain($unattachedSubscriber->getKey())
        ->not->toContain($otherSiteSubscriber->getKey());
});

it('includes matching saved filter subscribers and excludes non matching subscribers', function (): void {
    $site = $this->createNewsletterSite();
    $matchingSubscriber = Subscriber::factory()->create([
        'site_id' => $site->getKey(),
        'email' => 'matching@example.com',
        'status' => SubscriberStatus::Subscribed,
        'source_form_handle' => 'footer-signup',
        'created_at' => now()->subDays(2),
    ]);
    $wrongStatusSubscriber = Subscriber::factory()->create([
        'site_id' => $site->getKey(),
        'email' => 'pending@example.com',
        'status' => SubscriberStatus::Pending,
        'source_form_handle' => 'footer-signup',
        'created_at' => now()->subDays(2),
    ]);
    $wrongSourceSubscriber = Subscriber::factory()->create([
        'site_id' => $site->getKey(),
        'email' => 'sidebar@example.com',
        'status' => SubscriberStatus::Subscribed,
        'source_form_handle' => 'sidebar-signup',
        'created_at' => now()->subDays(2),
    ]);
    $tooOldSubscriber = Subscriber::factory()->create([
        'site_id' => $site->getKey(),
        'email' => 'old@example.com',
        'status' => SubscriberStatus::Subscribed,
        'source_form_handle' => 'footer-signup',
        'created_at' => now()->subDays(10),
    ]);
    $otherSiteSubscriber = Subscriber::factory()->create([
        'site_id' => $this->createNewsletterSite('Other')->getKey(),
        'email' => 'other-site@example.com',
        'status' => SubscriberStatus::Subscribed,
        'source_form_handle' => 'footer-signup',
        'created_at' => now()->subDays(2),
    ]);
    $segment = Segment::query()->create([
        'site_id' => $site->getKey(),
        'name' => 'Recent footer subscribers',
        'handle' => 'recent-footer-subscribers',
        'type' => SegmentType::SavedFilter,
        'filters' => [
            'status' => SubscriberStatus::Subscribed->value,
            'source_form_handle' => 'footer-signup',
            'created_from' => now()->subDays(3)->toDateString(),
            'created_until' => now()->toDateString(),
        ],
        'is_active' => true,
    ]);

    expect(segmentSubscriberIds($segment))->toBe([
        $matchingSubscriber->getKey(),
    ])
        ->not->toContain($wrongStatusSubscriber->getKey())
        ->not->toContain($wrongSourceSubscriber->getKey())
        ->not->toContain($tooOldSubscriber->getKey())
        ->not->toContain($otherSiteSubscriber->getKey());
});

it('applies compound tag and provider sync filters as and clauses', function (): void {
    $site = $this->createNewsletterSite();
    $tag = Tag::query()->create([
        'name' => ['en' => 'Product'],
        'slug' => ['en' => 'product'],
        'type' => 'newsletter',
    ]);
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
        'name' => 'Primary audience',
        'remote_id' => 'audience-1',
        'is_default' => true,
        'sync_subscribed_only' => true,
    ]);
    $matchingSubscriber = Subscriber::factory()->create([
        'site_id' => $site->getKey(),
        'status' => SubscriberStatus::Subscribed,
    ]);
    $missingProviderMatchSubscriber = Subscriber::factory()->create([
        'site_id' => $site->getKey(),
        'status' => SubscriberStatus::Subscribed,
    ]);
    $missingTagMatchSubscriber = Subscriber::factory()->create([
        'site_id' => $site->getKey(),
        'status' => SubscriberStatus::Subscribed,
    ]);

    ApplyNewsletterTagsAction::run($matchingSubscriber, [$tag->getKey()]);
    ApplyNewsletterTagsAction::run($missingProviderMatchSubscriber, [$tag->getKey()]);

    ProviderSubscriber::query()->create([
        'subscriber_id' => $matchingSubscriber->getKey(),
        'provider_audience_id' => $audience->getKey(),
        'remote_id' => 'remote-1',
        'remote_status' => 'subscribed',
        'synced_at' => now(),
    ]);
    ProviderSubscriber::query()->create([
        'subscriber_id' => $missingTagMatchSubscriber->getKey(),
        'provider_audience_id' => $audience->getKey(),
        'remote_id' => 'remote-2',
        'remote_status' => 'subscribed',
        'synced_at' => now(),
    ]);
    $segment = Segment::query()->create([
        'site_id' => $site->getKey(),
        'name' => 'Synced tagged subscribers',
        'handle' => 'synced-tagged-subscribers',
        'type' => SegmentType::SavedFilter,
        'filters' => [
            'status' => SubscriberStatus::Subscribed->value,
            'tag_ids' => [(string) $tag->getKey()],
            'provider_sync_status' => 'subscribed',
        ],
        'is_active' => true,
    ]);

    expect(segmentSubscriberIds($segment))->toBe([
        $matchingSubscriber->getKey(),
    ])
        ->not->toContain($missingProviderMatchSubscriber->getKey())
        ->not->toContain($missingTagMatchSubscriber->getKey());
});

it('ignores empty and invalid saved filter payloads while retaining the site scope', function (): void {
    $site = $this->createNewsletterSite();
    $firstSubscriber = Subscriber::factory()->create(['site_id' => $site->getKey()]);
    $secondSubscriber = Subscriber::factory()->create(['site_id' => $site->getKey()]);
    $otherSiteSubscriber = Subscriber::factory()->create(['site_id' => $this->createNewsletterSite('Other')->getKey()]);
    $emptySegment = Segment::query()->create([
        'site_id' => $site->getKey(),
        'name' => 'Empty filters',
        'handle' => 'empty-filters',
        'type' => SegmentType::SavedFilter,
        'filters' => null,
        'is_active' => true,
    ]);
    $invalidSegment = Segment::query()->create([
        'site_id' => $site->getKey(),
        'name' => 'Invalid filters',
        'handle' => 'invalid-filters',
        'type' => SegmentType::SavedFilter,
        'filters' => [
            'status' => ['subscribed'],
            'source_form_handle' => false,
            'created_from' => 123,
            'created_until' => null,
            'tag_ids' => 'not-an-array',
            'provider_sync_status' => ['subscribed'],
        ],
        'is_active' => true,
    ]);
    $expectedSubscriberIds = [
        $firstSubscriber->getKey(),
        $secondSubscriber->getKey(),
    ];

    expect(segmentSubscriberIds($emptySegment))->toBe($expectedSubscriberIds)
        ->and(segmentSubscriberIds($invalidSegment))->toBe($expectedSubscriberIds)
        ->not->toContain($otherSiteSubscriber->getKey());
});

/**
 * @return array<int, int|string>
 */
function segmentSubscriberIds(Segment $segment): array
{
    return EvaluateNewsletterSegmentAction::run($segment)
        ->orderBy('id')
        ->pluck('id')
        ->all();
}
