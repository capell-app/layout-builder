# Subscription Workflow

Newsletter owns subscriber state, consent evidence, provider sync attempts, and public confirmation/unsubscribe routes. Form Builder can feed it, but subscription rules stay in Newsletter actions.

## Main Flow

1. A form submission or package action calls `SubscribeFromFormSubmissionAction` or `UpsertSubscriberAction`.
2. The subscriber is created or updated with a `SubscriberStatus`.
3. Consent evidence is written when the source supplied a consent field.
4. Tags are applied through `ApplyNewsletterTagsAction`.
5. If the subscriber is fully subscribed, `QueueProviderSyncAction` queues provider sync.
6. If double opt-in is required, `RequestDoubleOptInAction` creates a public confirm token.

Use `ConfirmSubscriberAction` for Capell-owned double opt-in links and `UnsubscribeSubscriberAction` for public unsubscribe links.

## Form Builder Mapping

`SubscribeFromFormSubmissionAction` listens for form submissions and looks up an active `FormMapping` for the form's site. It matches by `form_id` or `form_handle`.

Required mapping fields:

| Mapping field                          | Purpose                                                                              |
| -------------------------------------- | ------------------------------------------------------------------------------------ |
| `email_field`                          | Payload key containing the email address.                                            |
| `consent_field`                        | Optional payload key that must evaluate to true before consent evidence is recorded. |
| `first_name_field` / `last_name_field` | Optional profile fields.                                                             |
| `fixed_tag_ids`                        | Newsletter tags applied to every matching subscriber.                                |
| `field_tag_mappings`                   | Payload value to tag mappings.                                                       |
| `requires_double_opt_in`               | Controls pending versus subscribed status.                                           |
| `confirmation_mode`                    | `capell_owned` or provider-owned confirmation.                                       |

## Subscribe Directly

```php
use Capell\Newsletter\Actions\UpsertSubscriberAction;
use Capell\Newsletter\Data\SubscriberData;
use Capell\Newsletter\Enums\SubscriberStatus;

$subscriber = UpsertSubscriberAction::run(new SubscriberData(
    siteId: $site->getKey(),
    email: 'person@example.test',
    status: SubscriberStatus::Pending,
));
```

Call `RequestDoubleOptInAction` after this when the subscriber must confirm through Capell before provider sync.

## Apply Newsletter Tags

```php
use Capell\Newsletter\Actions\ApplyNewsletterTagsAction;

ApplyNewsletterTagsAction::run($subscriber, [$tagId], replace: false);
```

The action only accepts tags whose type matches `capell-newsletter.newsletter_tag_type`, which defaults to `newsletter`.

## Provider Adapters

Provider adapters implement `NewsletterProviderAdapter`:

```php
use Capell\Newsletter\Contracts\NewsletterProviderAdapter;
use Capell\Newsletter\Data\ProviderAudienceData;
use Capell\Newsletter\Data\ProviderSubscriberData;
use Capell\Newsletter\Data\ProviderSyncResultData;
use Capell\Newsletter\Data\ProviderWebhookEventData;
use Capell\Newsletter\Models\ProviderAudience;
use Capell\Newsletter\Models\ProviderConnection;
use Illuminate\Http\Request;

final class DemoNewsletterAdapter implements NewsletterProviderAdapter
{
    public function supportsOAuth(): bool
    {
        return false;
    }

    public function supportsProviderOwnedConfirmation(): bool
    {
        return false;
    }

    /** @return array<int, ProviderAudienceData> */
    public function listAudiences(ProviderConnection $connection): array
    {
        return [];
    }

    public function syncSubscriber(
        ProviderConnection $connection,
        ProviderAudience $audience,
        ProviderSubscriberData $subscriber,
    ): ProviderSyncResultData {
        return new ProviderSyncResultData(successful: true);
    }

    public function verifyWebhook(ProviderConnection $connection, Request $request): bool
    {
        return true;
    }

    public function normalizeWebhook(ProviderConnection $connection, Request $request): ?ProviderWebhookEventData
    {
        return null;
    }
}
```

Keep provider failures in `ProviderSyncResultData` where possible. Throwing from an adapter should mean the attempt could not complete, not that the provider rejected a subscriber.

## Audience And Segment Extension Points

`NewsletterAudienceRegistry` accepts `NewsletterAudienceProvider` implementations. The built-in `SegmentAudienceProvider` returns active `Segment` records for a site.

`NewsletterSegmentProvider` exists for segment-specific subscriber queries:

```php
use Capell\Newsletter\Contracts\NewsletterSegmentProvider;
use Capell\Newsletter\Models\Segment;
use Capell\Newsletter\Models\Subscriber;
use Illuminate\Database\Eloquent\Builder;

final class RecentPurchaserSegmentProvider implements NewsletterSegmentProvider
{
    /** @return Builder<Subscriber> */
    public function querySubscribers(Segment $segment): Builder
    {
        return Subscriber::query()->where('site_id', $segment->site_id);
    }
}
```

## Configuration

| Key                                                         | Purpose                                                                                              |
| ----------------------------------------------------------- | ---------------------------------------------------------------------------------------------------- |
| `capell-newsletter.tables.*`                                | Table names for subscribers, consent events, provider records, segments, form mappings, and imports. |
| `capell-newsletter.double_opt_in.enabled_by_default`        | Default double opt-in state for mappings and manual flows.                                           |
| `capell-newsletter.double_opt_in.default_confirmation_mode` | Default confirmation owner.                                                                          |
| `capell-newsletter.double_opt_in.token_expiry_hours`        | Confirm token lifetime.                                                                              |
| `capell-newsletter.resubscribe_policy`                      | Default policy for resubscribing previously known subscribers.                                       |
| `capell-newsletter.newsletter_tag_type`                     | Tag type accepted by `ApplyNewsletterTagsAction`.                                                    |
| `capell-newsletter.sync.queue`                              | Queue for provider sync jobs.                                                                        |
| `capell-newsletter.sync.retry_minutes`                      | Retry schedule used for provider sync attempts.                                                      |

## Public Routes

| Route                                                     | Purpose                                                             |
| --------------------------------------------------------- | ------------------------------------------------------------------- |
| `GET /newsletter/confirm/{token}`                         | Confirms a Capell-owned double opt-in token.                        |
| `GET /newsletter/unsubscribe/{token}`                     | Unsubscribes by public token.                                       |
| `POST /newsletter/providers/{providerConnection}/webhook` | Receives provider webhooks and normalizes them through the adapter. |

## Retry Command

```text
newsletter:sync-retry-due {--limit=}
```

Use this to requeue due provider sync attempts. It should not be used as a replacement for queue workers.
