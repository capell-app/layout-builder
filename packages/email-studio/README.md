# Email Studio

Template-driven transactional email, delivery auditing, provider events, replies, and suppressions for Capell CMS.

## At A Glance

- Package: `capell-app/email-studio`
- Namespace: `Capell\EmailStudio\`
- Surfaces: HTTP, queue, database
- Service providers: `packages/email-studio/src/Providers/AdminServiceProvider.php`, `packages/email-studio/src/Providers/EmailStudioServiceProvider.php`, `packages/email-studio/src/Providers/FrontendServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/core`, `capell-app/frontend`

## What It Adds

- Template-driven transactional email, delivery auditing, provider events, replies, and suppressions for Capell CMS.

## Why It Matters

For developers, Email Studio removes the need for each package to build its own mailer conventions. Packages pass a `SendEmailData` object into one Action and get rendering, suppression checks, queue dispatch, provider selection, and audit records through the same path.

For teams, it creates one place to answer practical support questions:

- Which template was used?
- Which profile sent it?
- Who received it?
- Was the recipient suppressed?
- Did the provider accept or reject the message?
- Which payload and context were used at send time?

That is the part clients pay for. Sending an email is easy; proving what happened later is where the product value sits.

## Technical Shape

- `EmailStudioServiceProvider` registers config, translations, routes, migrations, models, and provider adapters.
- `AdminServiceProvider` and `FrontendServiceProvider` reserve the admin and public route surfaces for later slices.
- `EmailTemplateRegistry` stores package-owned template registrations.
- `EmailVariableRenderer` performs controlled `{{ variable }}` substitution using declared template variables.
- `EmailProfileResolver` resolves a requested profile or the best default profile for the site scope.
- `EmailProviderRegistry` isolates provider adapters from the send pipeline.
- `SendEmailAction` creates the message and recipient records, renders the selected variant, applies suppression state, and queues delivery.
- `DeliverEmailMessageAction` rechecks suppressions, calls the provider adapter, and records recipient/message outcomes.

## Code Map

| Area      | Path                                  | Purpose                                                             |
| --------- | ------------------------------------- | ------------------------------------------------------------------- |
| Actions   | `packages/email-studio/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/email-studio/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/email-studio/src/Enums`     | Persisted states and Filament option values.                        |
| Models    | `packages/email-studio/src/Models`    | Eloquent records owned by the package.                              |
| Jobs      | `packages/email-studio/src/Jobs`      | Queued work and async side effects.                                 |
| Providers | `packages/email-studio/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/email-studio/resources`     | Views, translations, assets, and package resources.                 |
| Routes    | `packages/email-studio/routes`        | Route files loaded by the service provider.                         |
| Config    | `packages/email-studio/config`        | Package configuration and publishable config.                       |
| Database  | `packages/email-studio/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/email-studio/tests`         | Package-level Pest coverage.                                        |

## Runtime Surface

- Routes: `packages/email-studio/routes/web.php`.
- Jobs: `SendEmailJob`.

## Data And Persistence

- Models: `EmailEvent`, `EmailMessage`, `EmailProfile`, `EmailRecipient`, `EmailReply`, `EmailSuppression`, `EmailTemplate`, `EmailTemplateRegistration`, `EmailTemplateVariant`, `EmailTrackingToken`.
- Migrations: `2026_05_10_190847_01_create_email_profiles_table.php`, `2026_05_10_190847_02_create_email_templates_table.php`, `2026_05_10_190847_03_create_email_template_variants_table.php`, `2026_05_10_190847_04_create_email_messages_table.php`, `2026_05_10_190847_05_create_email_recipients_table.php`, `2026_05_10_190847_06_create_email_events_table.php`, `2026_05_10_190847_07_create_email_replies_table.php`, `2026_05_10_190847_08_create_email_suppressions_table.php`, `2026_05_10_190847_09_create_email_template_registrations_table.php`, `2026_05_10_190847_10_create_email_tracking_tokens_table.php`.
- Config: `packages/email-studio/config/capell-email-studio.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Contracts: `EmailProviderAdapter`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install And Setup

- Install with `composer require capell-app/email-studio` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Common Pitfalls

- Only approved templates can be used for production sends.
- Only active variants are resolved.
- A send with no recipients fails before creating a message record.
- Suppressions are checked twice because an address can be suppressed after queueing and before delivery.
- Provider-level failures and adapter exceptions are recorded on the Email Studio message and recipients.
- If no locale is provided to `SendEmailData`, the send uses Laravel's current locale and falls back to a neutral variant.

## Docs

- [email-studio-api.md](docs/email-studio-api.md)
- [email-studio-database.md](docs/email-studio-database.md)
- [overview.md](docs/overview.md)
- [templates-and-providers.md](docs/templates-and-providers.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/email-studio/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
