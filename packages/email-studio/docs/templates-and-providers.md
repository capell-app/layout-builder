# Templates And Providers

Email Studio separates template registration, message creation, delivery, tracking, and provider webhooks. Package code should call Actions and registries rather than creating message rows by hand.

## Register a Template

Use `EmailTemplateRegistry` from a service provider when another package needs a template available to editors.

```php
use Capell\EmailStudio\Support\EmailTemplateRegistry;

$this->app->afterResolving(EmailTemplateRegistry::class, static function (EmailTemplateRegistry $registry): void {
    $registry->register(
        key: 'access-approved',
        name: 'Access approved',
        variables: ['name', 'claim_url'],
        description: 'Sent when an access request is approved.',
        packageName: 'capell-app/access-gate',
    );
});
```

The registry persists its registrations through `RegisterEmailTemplateAction`. Keep the key stable; editors may already have variants attached to it.

## Send an Email

Use `SendEmailAction` with `SendEmailData`. It resolves the profile, approved template, matching variant, suppression status, recipients, queue, and rendered body.

```php
use Capell\EmailStudio\Actions\SendEmailAction;
use Capell\EmailStudio\Data\EmailAddressData;
use Capell\EmailStudio\Data\EmailHeaderData;
use Capell\EmailStudio\Data\SendEmailData;
use Spatie\LaravelData\DataCollection;

$message = SendEmailAction::run(new SendEmailData(
    templateKey: 'access-approved',
    to: EmailAddressData::collect([
        new EmailAddressData(email: 'sam@example.test', name: 'Sam Editor'),
    ], DataCollection::class),
    cc: EmailAddressData::collect([], DataCollection::class),
    bcc: EmailAddressData::collect([], DataCollection::class),
    siteId: 1,
    siteScopeKey: 'global',
    emailProfileId: null,
    variables: [
        'name' => 'Sam Editor',
        'claim_url' => 'https://example.test/access/claim/token',
    ],
    headers: EmailHeaderData::collect([], DataCollection::class),
    triggeredByType: null,
    triggeredById: null,
));
```

If the constructor shape changes, update this example with the data class.

## Register a Provider Adapter

Provider adapters implement `EmailProviderAdapter`. They normalize outbound delivery, webhook payloads, and inbound replies.

```php
use Capell\EmailStudio\Contracts\EmailProviderAdapter;
use Capell\EmailStudio\Data\InboundEmailReplyData;
use Capell\EmailStudio\Data\ProviderSendResultData;
use Capell\EmailStudio\Data\ProviderWebhookEventData;
use Capell\EmailStudio\Enums\EmailProviderType;
use Capell\EmailStudio\Models\EmailMessage;
use Capell\EmailStudio\Support\EmailProviderRegistry;

final class DemoEmailProviderAdapter implements EmailProviderAdapter
{
    public function send(EmailMessage $message): ProviderSendResultData
    {
        return new ProviderSendResultData(successful: true);
    }

    public function normalizeWebhookPayload(array $payload, array $headers = []): ProviderWebhookEventData
    {
        return new ProviderWebhookEventData(
            provider: 'demo',
            eventType: (string) ($payload['event'] ?? 'delivered'),
            providerMessageId: isset($payload['message_id']) ? (string) $payload['message_id'] : null,
            recipientEmail: isset($payload['email']) ? (string) $payload['email'] : null,
            payload: $payload,
        );
    }

    public function normalizeInboundReply(array $payload, array $headers = []): InboundEmailReplyData
    {
        return new InboundEmailReplyData(
            provider: 'demo',
            providerMessageId: isset($payload['message_id']) ? (string) $payload['message_id'] : null,
            fromEmail: (string) ($payload['from_email'] ?? ''),
            fromName: isset($payload['from_name']) ? (string) $payload['from_name'] : null,
            subject: isset($payload['subject']) ? (string) $payload['subject'] : null,
            textBody: isset($payload['text']) ? (string) $payload['text'] : null,
            htmlBody: isset($payload['html']) ? (string) $payload['html'] : null,
            payload: $payload,
        );
    }
}

$this->app->afterResolving(EmailProviderRegistry::class, static function (EmailProviderRegistry $registry): void {
    $registry->register(EmailProviderType::Fake, new DemoEmailProviderAdapter);
});
```

Use a new `EmailProviderType` case before registering a real provider. The `Fake` case is for local/test behavior.

## Config Keys

| Key                                             | Use                                                                                          |
| ----------------------------------------------- | -------------------------------------------------------------------------------------------- |
| `capell-email-studio.default_provider`          | Provider used when an email profile does not override it.                                    |
| `capell-email-studio.queue`                     | Queue used by `SendEmailJob`. Can be set with `CAPELL_EMAIL_STUDIO_QUEUE`.                   |
| `capell-email-studio.track_opens`               | Enables open tracking where the provider supports it.                                        |
| `capell-email-studio.track_clicks`              | Enables click tracking where the provider supports it.                                       |
| `capell-email-studio.body_retention_days`       | How long rendered message bodies should be retained.                                         |
| `capell-email-studio.webhook_tolerance_seconds` | Tolerance window for provider webhook validation.                                            |
| `capell-email-studio.public_route_prefix`       | Prefix for tracking and webhook routes. Can be set with `CAPELL_EMAIL_STUDIO_PUBLIC_PREFIX`. |
| `capell-email-studio.tracking_token_ttl_days`   | Lifetime of tracking tokens.                                                                 |
| `capell-email-studio.webhook_rate_limit`        | Rate limiter name for webhooks.                                                              |
| `capell-email-studio.tracking_rate_limit`       | Rate limiter name for tracking routes.                                                       |

Table-name keys are part of install and migration behavior. Document them in migration notes rather than setup prose unless a host app needs custom table names.

## Verification

```bash
vendor/bin/pest packages/email-studio/tests --configuration=phpunit.xml
```
