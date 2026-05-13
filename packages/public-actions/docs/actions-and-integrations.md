# Public Actions And Integrations

Public Actions turns public submissions into stored records and optional outbound dispatches. Treat every public route payload as untrusted; validation and side effects belong in actions or registered handlers.

## HTTP Surface

| Route group         | Default prefix              | Purpose                                                                      |
| ------------------- | --------------------------- | ---------------------------------------------------------------------------- |
| Public action forms | `actions`                   | Show and submit a public action by key.                                      |
| Zapier API          | `api/public-actions/zapier` | Let Zapier authenticate, list actions, submit actions, and read submissions. |

The public submit route uses the `public-actions-submit` throttle. The Zapier routes use `public-actions-api` and `PublicActionZapierAuthMiddleware`.

## Register A Public Action Handler

Handlers implement `PublicActionHandler`. Access Gate uses this path through `SubmitAccessGatePublicAction`.

```php
use Capell\PublicActions\Contracts\PublicActionHandler;
use Capell\PublicActions\Data\PublicActionResultData;
use Capell\PublicActions\Data\PublicActionSubmissionData;

final class DemoSignupHandler implements PublicActionHandler
{
    public function handle(PublicActionSubmissionData $submission): PublicActionResultData
    {
        return new PublicActionResultData(
            success: true,
            message: __('capell-public-actions::generic.submitted'),
        );
    }
}
```

Register the handler with `PublicActionHandlerRegistry`:

```php
use Capell\PublicActions\Support\PublicActionHandlerRegistry;

$this->app->afterResolving(
    PublicActionHandlerRegistry::class,
    static function (PublicActionHandlerRegistry $registry): void {
        $registry->register('demo-signup', DemoSignupHandler::class);
    },
);
```

The key should match the handler key stored on the `PublicAction` record.

## Register A Destination Adapter

Destination adapters send stored submissions to external systems. They must return `PublicActionDispatchResultData`; do not throw for normal provider rejections.

```php
use Capell\PublicActions\Contracts\PublicActionDestinationAdapter;
use Capell\PublicActions\Data\PublicActionDispatchResultData;
use Capell\PublicActions\Models\PublicActionDestination;
use Capell\PublicActions\Models\PublicActionSubmission;

final class DemoCrmAdapter implements PublicActionDestinationAdapter
{
    public function dispatch(
        PublicActionDestination $destination,
        PublicActionSubmission $submission,
    ): PublicActionDispatchResultData {
        return new PublicActionDispatchResultData(success: true);
    }
}
```

Register it with `PublicActionDestinationAdapterRegistry`:

```php
use Capell\PublicActions\Support\PublicActionDestinationAdapterRegistry;

$this->app->afterResolving(
    PublicActionDestinationAdapterRegistry::class,
    static function (PublicActionDestinationAdapterRegistry $registry): void {
        $registry->register('demo_crm', DemoCrmAdapter::class);
    },
);
```

`DispatchPublicActionDestinationAction` fails with `InvalidArgumentException` when a destination references an adapter key that is not registered.

## Provider Presets

Provider presets are config defaults over the built-in `http_webhook` adapter. Add a preset when the provider still receives a normal HTTP webhook and only needs different labels/defaults.

```php
'adapters' => [
    'presets' => [
        'demo_crm' => [
            'adapter' => 'http_webhook',
            'method' => 'POST',
            'expects_json' => true,
        ],
    ],
],
```

Use a destination adapter instead when the provider needs signing, OAuth, polling, custom retries, or a non-HTTP transport.

## Integration Tokens

Use `CreatePublicActionIntegrationTokenAction` when a provider needs API access:

```php
use Capell\PublicActions\Actions\CreatePublicActionIntegrationTokenAction;
use Capell\PublicActions\Enums\PublicActionIntegrationProvider;

$token = CreatePublicActionIntegrationTokenAction::run(
    name: 'Zapier',
    provider: PublicActionIntegrationProvider::Zapier,
    siteId: $site->getKey(),
);

$plainTextToken = $token->plainTextToken;
```

The plain text token is returned once. Store only the hashed token in the database.

## Configuration

| Key                                                 | Purpose                                                                                       |
| --------------------------------------------------- | --------------------------------------------------------------------------------------------- |
| `capell-public-actions.route_prefix`                | Public web route prefix.                                                                      |
| `capell-public-actions.api_route_prefix`            | API route prefix for provider integrations.                                                   |
| `capell-public-actions.queue`                       | Queue used for destination dispatch.                                                          |
| `capell-public-actions.webhook_timeout_seconds`     | HTTP webhook timeout.                                                                         |
| `capell-public-actions.allow_insecure_webhook_urls` | Allows `http://` webhook URLs. Keep false outside local development.                          |
| `capell-public-actions.allow_private_webhook_urls`  | Allows private network webhook URLs. Keep false unless the deployment owns that network path. |
| `capell-public-actions.form_builder.mappings`       | Optional mappings from Form Builder submissions into public actions.                          |
| `capell-public-actions.tables.*`                    | Table names used by migrations and protected-table registration.                              |

## What To Test

Test the action handler directly for business behaviour, then one HTTP submission path for payload shape and throttling. Destination adapters should have a focused test for redaction, provider failure responses, and retryable failures.
