# Newsletter

Newsletter manages audiences, subscriptions, consent state, imports, notifications, and public subscription routes.

## At A Glance

- Package: `capell-app/newsletter`
- Namespace: `Capell\Newsletter\`
- Surfaces: Filament admin, console, HTTP, queue, database
- Service providers: `packages/newsletter/src/Providers/AdminServiceProvider.php`, `packages/newsletter/src/Providers/NewsletterServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/core`, `capell-app/form-builder`, `capell-app/frontend`, `capell-app/tags`

## What It Adds

- Newsletter manages audiences, subscriptions, consent state, imports, notifications, and public subscription routes.
- Admin resources: `FormMappingResource`, `ImportBatchResource`, `NewsletterTagResource`, `ProviderAudienceResource`, `ProviderConnectionResource`, `ProviderInterestMappingResource`, `SegmentResource`, `SubscriberResource`, `SyncAttemptResource`.
- Package setup or maintenance commands.

## Technical Shape

- NewsletterServiceProvider registers public routes, admin resources, jobs, listeners, migrations, settings, and translations.
- Subscription, import, consent, and notification behaviour lives in actions and jobs.
- Public endpoints route through controllers and should treat all payloads as untrusted input.

## Code Map

| Area      | Path                                | Purpose                                                             |
| --------- | ----------------------------------- | ------------------------------------------------------------------- |
| Actions   | `packages/newsletter/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/newsletter/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/newsletter/src/Enums`     | Persisted states and Filament option values.                        |
| Models    | `packages/newsletter/src/Models`    | Eloquent records owned by the package.                              |
| Filament  | `packages/newsletter/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| HTTP      | `packages/newsletter/src/Http`      | Controllers, middleware, and request handling.                      |
| Jobs      | `packages/newsletter/src/Jobs`      | Queued work and async side effects.                                 |
| Providers | `packages/newsletter/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/newsletter/resources`     | Views, translations, assets, and package resources.                 |
| Routes    | `packages/newsletter/routes`        | Route files loaded by the service provider.                         |
| Config    | `packages/newsletter/config`        | Package configuration and publishable config.                       |
| Database  | `packages/newsletter/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/newsletter/tests`         | Package-level Pest coverage.                                        |

## Admin Surface

- Resources: `FormMappingResource`, `ImportBatchResource`, `NewsletterTagResource`, `ProviderAudienceResource`, `ProviderConnectionResource`, `ProviderInterestMappingResource`, `SegmentResource`, `SubscriberResource`, `SyncAttemptResource`.
- Pages: `CreateFormMapping`, `CreateNewsletterTag`, `CreateProviderAudience`, `CreateProviderConnection`, `CreateProviderInterestMapping`, `CreateSegment`, `CreateSubscriber`, `EditFormMapping`, `EditNewsletterTag`, `EditProviderAudience`, `EditProviderConnection`, `EditProviderInterestMapping`, `EditSegment`, `EditSubscriber`, and related pages.
- Widgets: `NewsletterOverviewStatsWidget`.
- Settings: `NewsletterSettings`.

## Runtime Surface

- Controllers: `ConfirmSubscriptionController`, `ProviderWebhookController`, `UnsubscribeController`.
- Routes: `packages/newsletter/routes/web.php`.
- Jobs: `SyncSubscriberToProviderJob`.

## Commands

- `newsletter:sync-retry-due {--limit= : Maximum number of due attempts to requeue}` (packages/newsletter/src/Console/Commands/RequeueDueProviderSyncAttemptsCommand.php)

## Data And Persistence

- Models: `ConsentEvent`, `FormMapping`, `ImportBatch`, `ProviderAudience`, `ProviderConnection`, `ProviderInterestMapping`, `ProviderSubscriber`, `PublicToken`, `Segment`, `Subscriber`, `SyncAttempt`.
- Migrations: `2026_05_10_190861_01_create_newsletter_provider_connections_table.php`, `2026_05_10_190861_02_create_newsletter_subscribers_table.php`, `2026_05_10_190861_03_create_newsletter_provider_audiences_table.php`, `2026_05_10_190861_04_create_newsletter_consent_events_table.php`, `2026_05_10_190861_05_create_newsletter_provider_interest_mappings_table.php`, `2026_05_10_190861_06_create_newsletter_provider_subscribers_table.php`, `2026_05_10_190861_07_create_newsletter_public_tokens_table.php`, `2026_05_10_190861_08_create_newsletter_segments_table.php`, `2026_05_10_190861_09_create_newsletter_sync_attempts_table.php`, `2026_05_10_190861_10_create_newsletter_form_mappings_table.php`, `2026_05_10_190861_11_create_newsletter_import_batches_table.php`.
- Config: `packages/newsletter/config/capell-newsletter.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Contracts: `NewsletterAudienceProvider`, `NewsletterProviderAdapter`, `NewsletterSegmentProvider`.
- Listeners: `SubscribeFromFormSubmission`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install Impact

- Adds audience, subscriber, subscription, consent, import, segment, and notification tables.
- Adds newsletter admin resources and settings.
- Adds public subscription endpoints and async newsletter processing jobs.

## Install And Setup

- Install with `composer require capell-app/newsletter` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Docs

- [subscription-workflow.md](docs/subscription-workflow.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/newsletter/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Treat public routes as untrusted input and keep validation, permission checks, and side effects inside actions or dedicated services.
- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
