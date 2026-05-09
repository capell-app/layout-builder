# Email Studio Package Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build `capell-app/email-studio` as Capell's transactional email command centre for reusable templates, delivery profiles, send recording, provider events, replies, suppression, diagnostics, and package-level email extension points.

**Architecture:** Email Studio wraps Laravel Mail/Symfony Mailer behind Capell-owned Actions so every package can send audited, template-driven email without leaking transport details. The package owns templates, variants, profiles, messages, recipients, provider events, replies, suppressions, opaque public tracking tokens, and admin visibility; Newsletter continues to own audience growth and subscriber sync. Provider-specific behavior is isolated behind adapters and webhook Actions.

**Tech Stack:** PHP 8.2, Laravel 11/12/13, Filament 4/5, Pest, Lorisleiva Actions, Spatie Laravel Data, Laravel package tools, Capell Core/Admin/Frontend, optional FormBuilder/Newsletter/CampaignStudio integrations, Laravel Mail, Symfony Mailer, signed routes, queued jobs, fake/SMTP/Postmark provider support, and reserved follow-up provider contracts for Mailgun, SES, and Resend.

---

## Ground Rules

- Work in `/Users/ben/Sites/packages/capell/capell-packages-4`.
- Preserve the current dirty worktree. Only edit files created or changed for Email Studio.
- Do not modify `../capell-4` unless a core extension point is missing and the change is separately planned.
- Every PHP file must start with `declare(strict_types=1);`.
- PHP 8.2 only: no typed class constants, no readonly classes, no DNF types.
- Domain behavior lives in `packages/email-studio/src/Actions`.
- Structured boundary state lives in `packages/email-studio/src/Data`.
- Persisted statuses and option sets use backed enums implementing Filament labels where needed.
- User-facing strings use `__('capell-email-studio::...')`.
- Admin labels use method overrides and language files, not static string properties.
- Public frontend output must never expose admin/editor metadata, internal template IDs, provider IDs, signed editor URLs, or package internals.
- Public tracking and webhook URLs must use neutral configurable paths, opaque tokens, throttling, and signed URLs where appropriate. They must not expose recipient IDs, message IDs, template keys, package names, or admin concepts.
- Test Actions directly where possible, then add Filament/webhook tests for the external surfaces.

## Naming Decisions

- Package name: `capell-app/email-studio`.
- Namespace: `Capell\EmailStudio`.
- Main content model: `EmailTemplate`.
- Template version/context model: `EmailTemplateVariant`.
- Delivery identity/config model: `EmailProfile`.
- Sent item model: `EmailMessage`.
- Per-recipient delivery model: `EmailRecipient`.
- Provider lifecycle model: `EmailEvent`.
- Inbound response model: `EmailReply`.
- Suppression model: `EmailSuppression`.
- Public tracking token model: `EmailTrackingToken`.
- Use `email_template_id` for a template FK.
- Use `email_template_variant_id` when a send used a specific locale/site/version variant.
- Use `email_profile_id` for sender, reply-to, provider, and tracking configuration.
- Do not use `email_template_config_id`; it hides separate concepts that should remain queryable and testable.

## Product Scope

### MVP

- Site-scoped templates with subject, preview text, HTML body, plain-text body, variables, status, and versioning.
- Template variants for locale/site/profile-specific overrides.
- Profiles for sender identity, reply-to, provider type, tracking defaults, and webhook secrets.
- `SendEmailAction` as the canonical sending entrypoint.
- Queue-first sending through `SendEmailJob`.
- Full send recording: requested, queued, sent, failed, delivered, bounced, complained, opened, clicked, replied.
- Full provider support for fake, SMTP, and Postmark. Mailgun, SES, and Resend are registered as follow-up production adapters unless implemented fully in the same task.
- Webhook endpoints and Actions for delivery events, bounce/complaint events, clicks/opens, and inbound replies.
- Suppression checks before queueing and again immediately before provider handoff.
- Admin resources for templates, profiles, messages, recipients, replies, suppressions, and provider events.
- FormBuilder integration for form confirmation/admin notification emails.
- Package API so other Capell packages can register templates programmatically.
- Focused package docs and tests.

### Premium Product Layers

- Template approval workflow before production sends.
- Brand kits for site logo, colors, footer, legal text, and default profile.
- Reply inbox with assignment status and notes.
- Deliverability dashboard with provider failure reasons and suppression trend.
- Link tracking with UTM injection and optional CampaignStudio attribution.
- GDPR retention tools that redact bodies and rendered payload snapshots after a configured window.
- Template usage analytics and A/B variant selection.
- Developer diagnostics panel showing missing variables, invalid template syntax, and provider webhook health.
- Optional AI-assisted template drafting can be added later through `capell-app/ai-orchestrator`; it is not part of the first build.

## Boundaries

- Email Studio does not replace Newsletter. Newsletter owns subscribers, audiences, opt-in, imports, provider audience sync, and segment evaluation.
- Email Studio can consume Newsletter suppression/subscriber data through optional contracts.
- Email Studio does not replace CampaignStudio. CampaignStudio owns campaigns and conversions; Email Studio can append UTM values and emit email click events for attribution.
- Email Studio does not put authoring controls into public HTML. Preview and editing stay inside Filament/admin.
- Email Studio does not add a visual block editor in the first slice. Start with textarea/TinyEditor-compatible HTML editing and clean preview/testing.
- Email Studio does not support attachments in v1. Add a documented extension point later rather than storing raw attachments before retention and privacy rules are settled.

## Site Scope, Privacy, And Authorization

- Every template, variant, profile, message, recipient, event, reply, suppression, registration, and tracking token stores `site_id` where the record belongs to one site.
- Global templates/profiles are allowed, but unique constraints must avoid nullable-site duplicates by storing a required `site_scope_key` such as `global` or `site:123`.
- All create/update/send Actions resolve and persist `site_scope_key`; models never infer it inside mutators.
- Admin resources must scope queries through Capell's current actor/site access pattern, following Newsletter's `SiteScope::applyForCurrentActor(...)` convention where applicable.
- Admin policies/resources must prevent users from viewing messages, replies, suppressions, provider events, profiles, or templates outside their assigned site scope.
- Suppressions may be global or site-scoped. The default is global for hard bounces and complaints, site-scoped for manual suppressions unless the admin explicitly chooses global.
- Public route controllers resolve records through opaque tokens, not IDs, and return generic responses for invalid/expired tokens.

## File Structure

Create these primary files:

- `packages/email-studio/composer.json`: package metadata and provider discovery.
- `packages/email-studio/capell.json`: Capell manifest.
- `packages/email-studio/config/capell-email-studio.php`: table names, provider defaults, tracking, queue, retention.
- `packages/email-studio/database/migrations/01_create_email_profiles_table.php`: delivery profile records.
- `packages/email-studio/database/migrations/02_create_email_templates_table.php`: reusable template records.
- `packages/email-studio/database/migrations/03_create_email_template_variants_table.php`: versioned/contextual template content.
- `packages/email-studio/database/migrations/04_create_email_messages_table.php`: send request records.
- `packages/email-studio/database/migrations/05_create_email_recipients_table.php`: per-recipient lifecycle records.
- `packages/email-studio/database/migrations/06_create_email_events_table.php`: provider lifecycle event records with idempotency keys.
- `packages/email-studio/database/migrations/07_create_email_replies_table.php`: inbound reply records.
- `packages/email-studio/database/migrations/08_create_email_suppressions_table.php`: suppression records with normalized email hashes.
- `packages/email-studio/database/migrations/09_create_email_template_registrations_table.php`: package-owned registered template definitions.
- `packages/email-studio/database/migrations/10_create_email_tracking_tokens_table.php`: opaque open/click tracking tokens.
- `packages/email-studio/database/factories/*.php`: factories for every model.
- `packages/email-studio/src/Providers/EmailStudioServiceProvider.php`: package registration, routes, config, migrations, models, settings.
- `packages/email-studio/src/Providers/AdminServiceProvider.php`: Filament resources, dashboard stats, navigation group.
- `packages/email-studio/src/Providers/FrontendServiceProvider.php`: reserved for safe public tracking routes and no authoring output.
- `packages/email-studio/src/Contracts/EmailProviderAdapter.php`: provider send/webhook interface.
- `packages/email-studio/src/Contracts/RegistersEmailTemplates.php`: package template registration interface.
- `packages/email-studio/src/Support/EmailProviderRegistry.php`: provider adapter lookup.
- `packages/email-studio/src/Support/EmailTemplateRegistry.php`: registered template definitions.
- `packages/email-studio/src/Support/EmailVariableRenderer.php`: controlled variable rendering.
- `packages/email-studio/src/Support/EmailProfileResolver.php`: default/site/profile resolution.
- `packages/email-studio/src/Support/Providers/FakeEmailProviderAdapter.php`: deterministic testing provider.
- `packages/email-studio/src/Support/Providers/SmtpEmailProviderAdapter.php`: Laravel mail transport adapter.
- `packages/email-studio/src/Support/Providers/PostmarkEmailProviderAdapter.php`: Postmark adapter.
- `packages/email-studio/src/Support/Providers/MailgunEmailProviderAdapter.php`: follow-up adapter, not selectable until production support is implemented.
- `packages/email-studio/src/Support/Providers/SesEmailProviderAdapter.php`: follow-up adapter, not selectable until production support is implemented.
- `packages/email-studio/src/Support/Providers/ResendEmailProviderAdapter.php`: follow-up adapter, not selectable until production support is implemented.
- `packages/email-studio/src/Data/SendEmailData.php`: send command input.
- `packages/email-studio/src/Data/EmailAddressData.php`: typed address/name pair.
- `packages/email-studio/src/Data/EmailHeaderData.php`: typed header name/value pair.
- `packages/email-studio/src/Data/EmailContextData.php`: render variables and model context metadata.
- `packages/email-studio/src/Data/RenderedEmailData.php`: subject, preview, HTML, text, headers.
- `packages/email-studio/src/Data/ProviderSendResultData.php`: provider message ID/status/result.
- `packages/email-studio/src/Data/ProviderWebhookEventData.php`: normalized webhook payload.
- `packages/email-studio/src/Data/InboundEmailReplyData.php`: normalized inbound reply payload.
- `packages/email-studio/src/Enums/EmailTemplateStatus.php`: draft, approved, archived.
- `packages/email-studio/src/Enums/EmailVariantStatus.php`: draft, active, retired.
- `packages/email-studio/src/Enums/EmailMessageStatus.php`: requested, queued, sent, failed, partially_failed.
- `packages/email-studio/src/Enums/EmailRecipientStatus.php`: queued, sent, delivered, bounced, complained, opened, clicked, replied, failed, suppressed.
- `packages/email-studio/src/Enums/EmailProviderType.php`: fake, smtp, postmark, mailgun, ses, resend.
- `packages/email-studio/src/Enums/EmailEventType.php`: sent, delivered, bounced, complained, opened, clicked, replied, failed.
- `packages/email-studio/src/Enums/SuppressionReason.php`: bounce, complaint, unsubscribe, manual, provider.
- `packages/email-studio/src/Enums/ResourceEnum.php`: admin resources.
- `packages/email-studio/src/Models/*.php`: models for each database table.
- `packages/email-studio/src/Actions/RegisterEmailTemplateAction.php`: register package-owned templates.
- `packages/email-studio/src/Actions/ResolveEmailTemplateVariantAction.php`: select the variant for a send.
- `packages/email-studio/src/Actions/RenderEmailTemplateAction.php`: render subject/html/text safely.
- `packages/email-studio/src/Actions/SendEmailAction.php`: create message/recipients and queue delivery.
- `packages/email-studio/src/Actions/DeliverEmailMessageAction.php`: call provider adapter and record send result.
- `packages/email-studio/src/Actions/RecordEmailEventAction.php`: normalize and persist provider lifecycle events.
- `packages/email-studio/src/Actions/RecordEmailReplyAction.php`: link inbound replies to messages/recipients.
- `packages/email-studio/src/Actions/SuppressEmailAddressAction.php`: add suppression.
- `packages/email-studio/src/Actions/CheckEmailSuppressionAction.php`: enforce suppression before send.
- `packages/email-studio/src/Actions/BuildEmailTimelineAction.php`: admin recipient/message timeline.
- `packages/email-studio/src/Actions/BuildEmailStudioOverviewStatsAction.php`: dashboard cards.
- `packages/email-studio/src/Actions/RedactExpiredEmailContentAction.php`: retention cleanup.
- `packages/email-studio/src/Jobs/SendEmailJob.php`: queued delivery.
- `packages/email-studio/src/Jobs/RedactExpiredEmailContentJob.php`: scheduled retention job.
- `packages/email-studio/src/Http/Controllers/EmailProviderWebhookController.php`: provider webhook entrypoint.
- `packages/email-studio/src/Http/Controllers/TrackEmailClickController.php`: signed click tracking redirect.
- `packages/email-studio/src/Http/Controllers/TrackEmailOpenController.php`: tracking pixel endpoint.
- `packages/email-studio/src/Listeners/SendFormSubmissionEmails.php`: FormBuilder integration.
- `packages/email-studio/src/Filament/Resources/**`: admin resources and pages.
- `packages/email-studio/src/Filament/Settings/EmailStudioSettingsSchema.php`: settings schema.
- `packages/email-studio/resources/lang/en/*.php`: package, navigation, form, table, actions, messages, settings, widgets.
- `packages/email-studio/routes/web.php`: signed public tracking routes and provider webhook routes.
- `packages/email-studio/docs/overview.md`: product overview.
- `packages/email-studio/docs/email-studio-api.md`: developer API.
- `packages/email-studio/docs/email-studio-database.md`: database reference.
- `packages/email-studio/docs/screenshots.json`: screenshot manifest.
- `packages/email-studio/tests/**`: unit, integration, feature, arch tests.

Modify these files:

- `composer.json`: add `Capell\\EmailStudio\\` and `Capell\\EmailStudio\\Database\\Factories\\` PSR-4 entries.
- `README.md`: add Email Studio to the Growth/Operations product table once the package exists.
- `docs/product-groups.md`: place Email Studio in a new or existing premium product group.

## Task 1: Package Skeleton And Registration

**Files:**

- Create: `packages/email-studio/composer.json`
- Create: `packages/email-studio/capell.json`
- Create: `packages/email-studio/config/capell-email-studio.php`
- Create: `packages/email-studio/resources/lang/en/package.php`
- Create: `packages/email-studio/resources/lang/en/generic.php`
- Create: `packages/email-studio/src/Providers/EmailStudioServiceProvider.php`
- Create: `packages/email-studio/src/Providers/AdminServiceProvider.php`
- Create: `packages/email-studio/src/Providers/FrontendServiceProvider.php`
- Create: `packages/email-studio/tests/Pest.php`
- Create: `packages/email-studio/tests/EmailStudioTestCase.php`
- Create: `packages/email-studio/tests/Unit/Providers/EmailStudioServiceProviderTest.php`
- Modify: `composer.json`

- [x] **Step 1: Write the failing provider smoke tests**

Create `packages/email-studio/tests/Pest.php`:

```php
<?php

declare(strict_types=1);

use Capell\EmailStudio\Tests\EmailStudioTestCase;

uses(EmailStudioTestCase::class)->in(__DIR__);
```

Create `packages/email-studio/tests/Unit/Providers/EmailStudioServiceProviderTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\EmailStudio\Providers\EmailStudioServiceProvider;

it('registers the email studio package metadata', function (): void {
    $package = CapellCore::getPackage(EmailStudioServiceProvider::$packageName);

    expect($package->name)->toBe(EmailStudioServiceProvider::$packageName);
});

it('loads the email studio config', function (): void {
    expect(config('capell-email-studio.tables.templates'))->toBe('email_templates')
        ->and(config('capell-email-studio.queue'))->toBe('default');
});
```

- [x] **Step 2: Run the smoke test and verify it fails**

Run:

```bash
vendor/bin/pest packages/email-studio/tests/Unit/Providers/EmailStudioServiceProviderTest.php --configuration=phpunit.xml
```

Expected: FAIL because `Capell\EmailStudio\Providers\EmailStudioServiceProvider` does not exist.

- [x] **Step 3: Add package metadata**

Create `packages/email-studio/composer.json`:

```json
{
    "name": "capell-app/email-studio",
    "description": "Template-driven transactional email, delivery auditing, provider events, replies, and suppressions for Capell CMS.",
    "keywords": ["capell", "email", "transactional-email", "laravel", "filamentphp", "cms"],
    "homepage": "https://github.com/capell-app/email-studio",
    "license": "proprietary",
    "authors": [
        {
            "name": "Capell Team",
            "email": "team@capell.app",
            "role": "Developer"
        }
    ],
    "require": {
        "php": "^8.2",
        "capell-app/admin": "*",
        "capell-app/core": "*",
        "capell-app/frontend": "*"
    },
    "require-dev": {
        "orchestra/testbench": "^9.0",
        "pestphp/pest": "^3.0",
        "pestphp/pest-plugin-laravel": "^3.0"
    },
    "autoload": {
        "psr-4": {
            "Capell\\EmailStudio\\": "src/",
            "Capell\\EmailStudio\\Database\\Factories\\": "database/factories"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Capell\\EmailStudio\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Capell\\EmailStudio\\Providers\\EmailStudioServiceProvider"
            ]
        }
    },
    "config": {
        "sort-packages": true
    },
    "scripts": {
        "test": "pest"
    },
    "prefer-stable": true
}
```

Create `packages/email-studio/capell.json`:

```json
{
    "name": "capell-app/email-studio",
    "description": "Template-driven transactional email, delivery auditing, provider events, replies, and suppressions.",
    "providers": {
        "shared": [
            "Capell\\EmailStudio\\Providers\\EmailStudioServiceProvider"
        ],
        "admin": [
            "Capell\\EmailStudio\\Providers\\AdminServiceProvider"
        ],
        "frontend": [
            "Capell\\EmailStudio\\Providers\\FrontendServiceProvider"
        ]
    },
    "dependencies": [
        "capell-app/admin",
        "capell-app/core",
        "capell-app/frontend"
    ],
    "tier": "premium",
    "category": "operations"
}
```

Create `packages/email-studio/config/capell-email-studio.php`:

```php
<?php

declare(strict_types=1);

return [
    'tables' => [
        'profiles' => 'email_profiles',
        'templates' => 'email_templates',
        'template_variants' => 'email_template_variants',
        'messages' => 'email_messages',
        'recipients' => 'email_recipients',
        'events' => 'email_events',
        'replies' => 'email_replies',
        'suppressions' => 'email_suppressions',
        'template_registrations' => 'email_template_registrations',
        'tracking_tokens' => 'email_tracking_tokens',
    ],
    'default_provider' => 'smtp',
    'queue' => env('CAPELL_EMAIL_STUDIO_QUEUE', 'default'),
    'track_opens' => true,
    'track_clicks' => true,
    'body_retention_days' => 90,
    'webhook_tolerance_seconds' => 300,
    'public_route_prefix' => env('CAPELL_EMAIL_STUDIO_PUBLIC_PREFIX', 'mail'),
    'tracking_token_ttl_days' => 180,
    'webhook_rate_limit' => 'email-studio-webhooks',
    'tracking_rate_limit' => 'email-studio-tracking',
];
```

- [x] **Step 4: Add providers and test case**

Create `packages/email-studio/src/Providers/EmailStudioServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

class EmailStudioServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-email-studio';

    public static string $packageName = 'capell-app/email-studio';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('capell-email-studio')
            ->hasTranslations()
            ->hasRoute('web')
            ->hasMigrations([
                '01_create_email_profiles_table',
                '02_create_email_templates_table',
                '03_create_email_template_variants_table',
                '04_create_email_messages_table',
                '05_create_email_recipients_table',
                '06_create_email_events_table',
                '07_create_email_replies_table',
                '08_create_email_suppressions_table',
                '09_create_email_template_registrations_table',
                '10_create_email_tracking_tokens_table',
            ]);
    }

    public function registeringPackage(): void
    {
        $this->app->register(AdminServiceProvider::class);
        $this->app->register(FrontendServiceProvider::class);
    }

    public function packageRegistered(): void
    {
        $this->registerPackageMetadata();
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/../..'),
            version: CapellCore::getInstalledPrettyVersion(self::$packageName),
            description: fn (): string => __('capell-email-studio::package.description'),
        );

        return $this;
    }
}
```

Create `packages/email-studio/src/Providers/AdminServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Providers;

use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'capell-email-studio');
    }
}
```

Create `packages/email-studio/src/Providers/FrontendServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Providers;

use Illuminate\Support\ServiceProvider;

class FrontendServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }
}
```

Create `packages/email-studio/resources/lang/en/package.php`:

```php
<?php

declare(strict_types=1);

return [
    'description' => 'Template-driven transactional email, delivery auditing, provider events, replies, and suppressions.',
];
```

Create `packages/email-studio/resources/lang/en/generic.php`:

```php
<?php

declare(strict_types=1);

return [
    'email_studio' => 'Email Studio',
];
```

Create `packages/email-studio/tests/EmailStudioTestCase.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Tests;

use Capell\EmailStudio\Providers\EmailStudioServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class EmailStudioTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            EmailStudioServiceProvider::class,
        ];
    }
}
```

- [x] **Step 5: Add root autoload entries**

Modify `composer.json` PSR-4 autoload:

```json
"Capell\\EmailStudio\\": "packages/email-studio/src",
"Capell\\EmailStudio\\Database\\Factories\\": "packages/email-studio/database/factories"
```

- [x] **Step 6: Refresh autoload and verify**

Run:

```bash
composer dump-autoload
vendor/bin/pest packages/email-studio/tests/Unit/Providers/EmailStudioServiceProviderTest.php --configuration=phpunit.xml
```

Expected: PASS.

- [x] **Step 7: Commit the package skeleton**

Run:

```bash
git add composer.json packages/email-studio
git commit -m "feat: add email studio package skeleton"
```

## Task 2: Core Database, Models, Factories, And Enums

**Files:**

- Create: all `packages/email-studio/database/migrations/*.php`
- Create: all `packages/email-studio/database/factories/*.php`
- Create: all `packages/email-studio/src/Enums/*.php`
- Create: all `packages/email-studio/src/Models/*.php`
- Create: `packages/email-studio/tests/Integration/Database/EmailStudioMigrationsTest.php`
- Create: `packages/email-studio/tests/Integration/Models/EmailStudioRelationshipsTest.php`

- [x] **Step 1: Write migration and relationship tests**

Create `packages/email-studio/tests/Integration/Database/EmailStudioMigrationsTest.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('creates the email studio tables', function (): void {
    foreach (config('capell-email-studio.tables') as $tableName) {
        expect(Schema::hasTable($tableName))->toBeTrue();
    }
});
```

Create `packages/email-studio/tests/Integration/Models/EmailStudioRelationshipsTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\EmailStudio\Models\EmailMessage;
use Capell\EmailStudio\Models\EmailProfile;
use Capell\EmailStudio\Models\EmailRecipient;
use Capell\EmailStudio\Models\EmailReply;
use Capell\EmailStudio\Models\EmailTemplate;
use Capell\EmailStudio\Models\EmailTemplateVariant;

it('links templates, variants, messages, recipients, and replies', function (): void {
    $profile = EmailProfile::factory()->create();
    $template = EmailTemplate::factory()->create();
    $variant = EmailTemplateVariant::factory()->for($template, 'template')->create();
    $message = EmailMessage::factory()
        ->for($profile, 'profile')
        ->for($template, 'template')
        ->for($variant, 'templateVariant')
        ->create();
    $recipient = EmailRecipient::factory()->for($message, 'message')->create();
    $reply = EmailReply::factory()
        ->for($message, 'message')
        ->for($recipient, 'recipient')
        ->create();

    expect($message->template->is($template))->toBeTrue()
        ->and($message->templateVariant->is($variant))->toBeTrue()
        ->and($message->recipients)->toHaveCount(1)
        ->and($recipient->replies)->toHaveCount(1)
        ->and($reply->message->is($message))->toBeTrue();
});
```

- [x] **Step 2: Run tests and verify they fail**

Run:

```bash
vendor/bin/pest packages/email-studio/tests/Integration/Database/EmailStudioMigrationsTest.php packages/email-studio/tests/Integration/Models/EmailStudioRelationshipsTest.php --configuration=phpunit.xml
```

Expected: FAIL because migrations, models, and factories do not exist.

- [x] **Step 3: Add enums**

Create:

```php
<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Enums;

use Filament\Support\Contracts\HasLabel;

enum EmailTemplateStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return __("capell-email-studio::generic.statuses.template.{$this->value}");
    }
}
```

Repeat the same enum pattern for:

- `EmailVariantStatus`: `draft`, `active`, `retired`.
- `EmailMessageStatus`: `requested`, `queued`, `sent`, `failed`, `partially_failed`.
- `EmailRecipientStatus`: `queued`, `sent`, `delivered`, `bounced`, `complained`, `opened`, `clicked`, `replied`, `failed`, `suppressed`.
- `EmailProviderType`: `fake`, `smtp`, `postmark`, `mailgun`, `ses`, `resend`.
- `EmailEventType`: `sent`, `delivered`, `bounced`, `complained`, `opened`, `clicked`, `replied`, `failed`.
- `SuppressionReason`: `bounce`, `complaint`, `unsubscribe`, `manual`, `provider`.

- [x] **Step 4: Add migrations**

Create migrations with these required columns:

```php
Schema::create('email_profiles', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('site_id')->nullable()->index();
    $table->string('site_scope_key')->default('global')->index();
    $table->string('name');
    $table->string('provider')->index();
    $table->string('webhook_endpoint_token_hash')->nullable()->unique();
    $table->string('from_email');
    $table->string('from_name')->nullable();
    $table->string('reply_to_email')->nullable();
    $table->string('reply_to_name')->nullable();
    $table->boolean('is_default')->default(false);
    $table->boolean('track_opens')->default(true);
    $table->boolean('track_clicks')->default(true);
    $table->json('provider_settings')->nullable();
    $table->timestamps();
});
```

```php
Schema::create('email_templates', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('site_id')->nullable()->index();
    $table->string('site_scope_key')->default('global')->index();
    $table->string('key')->index();
    $table->string('name');
    $table->string('status')->index();
    $table->text('description')->nullable();
    $table->json('variables')->nullable();
    $table->timestamps();
    $table->unique(['site_scope_key', 'key']);
});
```

```php
Schema::create('email_template_variants', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('site_id')->nullable()->index();
    $table->string('site_scope_key')->default('global')->index();
    $table->foreignId('email_template_id')->constrained()->cascadeOnDelete();
    $table->foreignId('email_profile_id')->nullable()->constrained()->nullOnDelete();
    $table->string('locale')->nullable()->index();
    $table->string('status')->index();
    $table->unsignedInteger('version')->default(1);
    $table->string('subject');
    $table->string('preview_text')->nullable();
    $table->longText('html_body');
    $table->longText('text_body')->nullable();
    $table->timestamp('approved_at')->nullable();
    $table->foreignId('approved_by')->nullable();
    $table->timestamps();
});
```

```php
Schema::create('email_messages', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('site_id')->nullable()->index();
    $table->string('site_scope_key')->default('global')->index();
    $table->foreignId('email_profile_id')->constrained()->restrictOnDelete();
    $table->foreignId('email_template_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('email_template_variant_id')->nullable()->constrained()->nullOnDelete();
    $table->string('status')->index();
    $table->string('subject');
    $table->string('preview_text')->nullable();
    $table->longText('rendered_html')->nullable();
    $table->longText('rendered_text')->nullable();
    $table->json('context_snapshot')->nullable();
    $table->json('headers')->nullable();
    $table->string('triggered_by_type')->nullable();
    $table->unsignedBigInteger('triggered_by_id')->nullable();
    $table->timestamp('queued_at')->nullable();
    $table->timestamp('sent_at')->nullable();
    $table->timestamp('failed_at')->nullable();
    $table->text('failure_reason')->nullable();
    $table->timestamps();
});
```

```php
Schema::create('email_recipients', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('site_id')->nullable()->index();
    $table->string('site_scope_key')->default('global')->index();
    $table->foreignId('email_message_id')->constrained()->cascadeOnDelete();
    $table->string('type')->default('to');
    $table->string('email')->index();
    $table->string('normalized_email')->index();
    $table->string('email_hash')->index();
    $table->string('name')->nullable();
    $table->string('status')->index();
    $table->string('provider_message_id')->nullable()->index();
    $table->timestamp('sent_at')->nullable();
    $table->timestamp('delivered_at')->nullable();
    $table->timestamp('opened_at')->nullable();
    $table->timestamp('clicked_at')->nullable();
    $table->timestamp('bounced_at')->nullable();
    $table->timestamp('complained_at')->nullable();
    $table->timestamp('replied_at')->nullable();
    $table->timestamp('suppressed_at')->nullable();
    $table->text('failure_reason')->nullable();
    $table->timestamps();
});
```

Also create:

- `email_events` with `site_id`, required `site_scope_key`, FK links to message/recipient/profile where relevant, `provider_event_id`, deterministic `idempotency_key`, raw provider payload JSON, event timestamp, and `unique(['email_profile_id', 'idempotency_key'])`.
- `email_replies` with `site_id`, required `site_scope_key`, FK links to message/recipient, normalized sender email/hash, raw provider payload JSON, reply body, and `received_at`.
- `email_suppressions` with `site_id`, required `site_scope_key`, email, normalized email, email hash, reason, source, timestamps, and uniqueness that prevents duplicate active suppressions for the same scope/email/reason.
- `email_template_registrations` with `site_id`, required `site_scope_key`, template key, package name, variables, and uniqueness on package/template/scope.
- `email_tracking_tokens` with `site_id`, required `site_scope_key`, `email_recipient_id`, opaque token hash, token type, destination URL, expiry, consumed timestamps where applicable, and no public route model binding.

- [x] **Step 5: Add models and factories**

Each model uses `HasFactory`, typed relationship methods, guarded `[]`, casts for enums/json/datetimes, and no side-effect methods. Example:

```php
<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Models;

use Capell\EmailStudio\Database\Factories\EmailMessageFactory;
use Capell\EmailStudio\Enums\EmailMessageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailMessage extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'status' => EmailMessageStatus::class,
        'context_snapshot' => 'array',
        'headers' => 'array',
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(EmailProfile::class, 'email_profile_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function templateVariant(): BelongsTo
    {
        return $this->belongsTo(EmailTemplateVariant::class, 'email_template_variant_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(EmailRecipient::class);
    }

    protected static function newFactory(): EmailMessageFactory
    {
        return EmailMessageFactory::new();
    }
}
```

- [x] **Step 6: Register models and protected tables**

In `EmailStudioServiceProvider`, after package metadata, register package models and protected tables when the package is installed:

```php
$this->app->booted(function (): void {
    if (! $this->isPackageInstalled()) {
        return;
    }

    $this->registerModels()->registerProtectedTables();
});
```

- [x] **Step 7: Verify migrations and relationships**

Run:

```bash
vendor/bin/pest packages/email-studio/tests/Integration/Database/EmailStudioMigrationsTest.php packages/email-studio/tests/Integration/Models/EmailStudioRelationshipsTest.php --configuration=phpunit.xml
```

Expected: PASS.

- [x] **Step 8: Commit the data model**

Run:

```bash
git add packages/email-studio
git commit -m "feat: add email studio data model"
```

## Task 3: Data Objects, Template Rendering, And Template Registration

**Files:**

- Create: `packages/email-studio/src/Data/EmailAddressData.php`
- Create: `packages/email-studio/src/Data/EmailHeaderData.php`
- Create: `packages/email-studio/src/Data/EmailContextData.php`
- Create: `packages/email-studio/src/Data/RenderedEmailData.php`
- Create: `packages/email-studio/src/Data/SendEmailData.php`
- Create: `packages/email-studio/src/Actions/RegisterEmailTemplateAction.php`
- Create: `packages/email-studio/src/Actions/ResolveEmailTemplateVariantAction.php`
- Create: `packages/email-studio/src/Actions/RenderEmailTemplateAction.php`
- Create: `packages/email-studio/src/Support/EmailTemplateRegistry.php`
- Create: `packages/email-studio/src/Support/EmailVariableRenderer.php`
- Create: `packages/email-studio/tests/Unit/Actions/RegisterEmailTemplateActionTest.php`
- Create: `packages/email-studio/tests/Unit/Actions/RenderEmailTemplateActionTest.php`

- [ ] **Step 1: Write failing registration and rendering tests**

Create tests proving:

- Registered templates are upserted by key and site.
- Rendering replaces declared variables only.
- HTML rendering escapes variables by default.
- Text rendering inserts plain strings without HTML escaping.
- Missing variables are preserved as visible `{{ variable }}` markers in preview mode.
- Missing variables fail production rendering with an exception.
- HTML and text bodies render from the same context.

Core expectation:

```php
$rendered = RenderEmailTemplateAction::run(
    variant: $variant,
    context: EmailContextData::from([
        'variables' => ['name' => 'Ben'],
        'preview' => false,
    ]),
);

expect($rendered->subject)->toBe('Hello Ben')
    ->and($rendered->html)->toContain('<p>Hello Ben</p>')
    ->and($rendered->text)->toContain('Hello Ben');
```

- [ ] **Step 2: Implement Data objects**

Use Spatie Data constructor-promoted public properties:

```php
class EmailAddressData extends Data
{
    public function __construct(
        public string $email,
        public ?string $name = null,
    ) {}
}
```

`SendEmailData` contains:

- `string $templateKey`
- `DataCollection $to` containing `EmailAddressData`
- `DataCollection $cc` containing `EmailAddressData`
- `DataCollection $bcc` containing `EmailAddressData`
- `?int $siteId`
- `string $siteScopeKey`
- `?int $emailProfileId`
- `array $variables`
- `DataCollection $headers` containing a dedicated `EmailHeaderData`
- `?string $triggeredByType`
- `?int $triggeredById`
- `bool $queue`

- [ ] **Step 3: Implement template registry**

`EmailTemplateRegistry` stores package registrations in memory during boot and persists them through `RegisterEmailTemplateAction`. Use typed registration methods:

```php
public function register(string $key, string $name, array $variables, ?string $description = null): self
```

- [ ] **Step 4: Implement safe variable rendering**

`EmailVariableRenderer` should support simple `{{ variable }}` replacement only. It should not evaluate PHP, Blade directives, arbitrary expressions, or nested object traversal in v1. For HTML bodies and subjects it must escape variable values with Laravel's `e()` helper before insertion. For plain text bodies it must cast scalar values to strings without HTML escaping. Do not add a raw-HTML syntax in v1; if a later package needs trusted HTML variables, add a typed `TrustedEmailHtmlData` boundary and tests first.

- [ ] **Step 5: Verify**

Run:

```bash
vendor/bin/pest packages/email-studio/tests/Unit/Actions/RegisterEmailTemplateActionTest.php packages/email-studio/tests/Unit/Actions/RenderEmailTemplateActionTest.php --configuration=phpunit.xml
```

Expected: PASS.

- [ ] **Step 6: Commit**

Run:

```bash
git add packages/email-studio
git commit -m "feat: add email template rendering"
```

## Task 4: Sending Pipeline, Provider Adapters, And Suppression

**Files:**

- Create: `packages/email-studio/src/Contracts/EmailProviderAdapter.php`
- Create: `packages/email-studio/src/Data/ProviderSendResultData.php`
- Create: `packages/email-studio/src/Support/EmailProviderRegistry.php`
- Create: `packages/email-studio/src/Support/EmailProfileResolver.php`
- Create: `packages/email-studio/src/Support/Providers/FakeEmailProviderAdapter.php`
- Create: `packages/email-studio/src/Support/Providers/SmtpEmailProviderAdapter.php`
- Create: `packages/email-studio/src/Support/Providers/PostmarkEmailProviderAdapter.php`
- Create: lightweight registration placeholders for Mailgun, SES, and Resend only if the UI clearly labels them unavailable until configured in a later provider task.
- Create: `packages/email-studio/src/Actions/CheckEmailSuppressionAction.php`
- Create: `packages/email-studio/src/Actions/SuppressEmailAddressAction.php`
- Create: `packages/email-studio/src/Actions/SendEmailAction.php`
- Create: `packages/email-studio/src/Actions/DeliverEmailMessageAction.php`
- Create: `packages/email-studio/src/Jobs/SendEmailJob.php`
- Create: `packages/email-studio/tests/Integration/Actions/SendEmailActionTest.php`
- Create: `packages/email-studio/tests/Integration/Actions/DeliverEmailMessageActionTest.php`
- Create: `packages/email-studio/tests/Integration/Actions/EmailSuppressionActionTest.php`

- [ ] **Step 1: Write failing tests**

Tests must prove:

- `SendEmailAction` creates one message and one recipient per address.
- `SendEmailAction` for site A cannot use a template, variant, or profile scoped only to site B.
- `EmailProfileResolver` chooses a profile from the requested site scope or a global fallback, never another site.
- `ResolveEmailTemplateVariantAction` chooses a variant from the requested site scope or a global fallback, never another site.
- Every message, recipient, event, reply, suppression, registration, and tracking token created by the send/event pipeline carries the resolved site ownership directly.
- Suppressed recipients are recorded as suppressed and not handed to the adapter.
- Recipients suppressed after queueing but before delivery are rechecked, marked suppressed, and not handed to the adapter.
- Queue mode dispatches `SendEmailJob`.
- Immediate mode calls `DeliverEmailMessageAction`.
- Fake provider marks recipients as sent and stores a deterministic provider message ID.

- [ ] **Step 2: Implement provider contract**

```php
interface EmailProviderAdapter
{
    public function send(EmailMessage $message): ProviderSendResultData;

    public function normalizeWebhookPayload(array $payload, array $headers = []): ProviderWebhookEventData;

    public function normalizeInboundReply(array $payload, array $headers = []): InboundEmailReplyData;
}
```

- [ ] **Step 3: Implement `SendEmailAction`**

Flow:

1. Resolve profile.
2. Resolve active variant for the same `site_scope_key`.
3. Render content.
4. Create message with rendered snapshot and resolved `site_id`/`site_scope_key`.
5. Create recipients with the same `site_id`/`site_scope_key`, checking suppressions per email address.
6. Queue `SendEmailJob` or deliver immediately.
7. Return the `EmailMessage`.

- [ ] **Step 4: Implement `DeliverEmailMessageAction`**

Flow:

1. Load message with recipients/profile.
2. Re-run `CheckEmailSuppressionAction` for every pending recipient immediately before provider handoff.
3. Mark newly suppressed recipients as suppressed and exclude them from the adapter payload.
4. Call adapter.
5. Persist provider message IDs.
6. Mark message `sent`, `failed`, or `partially_failed`.
7. Record failure reasons without throwing for provider-level soft failures.
8. Throw for invalid configuration so jobs fail loudly.

- [ ] **Step 5: Register adapters**

In `EmailStudioServiceProvider`, bind `EmailProviderRegistry` and register fake, SMTP, and Postmark as supported v1 providers. Mailgun, SES, and Resend can be reserved enum cases, but should not appear as selectable production providers until their adapters can send, verify webhooks, normalize events, and pass integration tests.

- [ ] **Step 6: Verify**

Run:

```bash
vendor/bin/pest packages/email-studio/tests/Integration/Actions/SendEmailActionTest.php packages/email-studio/tests/Integration/Actions/DeliverEmailMessageActionTest.php packages/email-studio/tests/Integration/Actions/EmailSuppressionActionTest.php --configuration=phpunit.xml
```

Expected: PASS.

- [ ] **Step 7: Commit**

Run:

```bash
git add packages/email-studio
git commit -m "feat: add email studio sending pipeline"
```

## Task 5: Provider Events, Replies, Tracking, And Retention

**Files:**

- Create: `packages/email-studio/src/Data/ProviderWebhookEventData.php`
- Create: `packages/email-studio/src/Data/InboundEmailReplyData.php`
- Create: `packages/email-studio/src/Actions/RecordEmailEventAction.php`
- Create: `packages/email-studio/src/Actions/RecordEmailReplyAction.php`
- Create: `packages/email-studio/src/Actions/BuildEmailTimelineAction.php`
- Create: `packages/email-studio/src/Actions/RedactExpiredEmailContentAction.php`
- Create: `packages/email-studio/src/Jobs/RedactExpiredEmailContentJob.php`
- Create: `packages/email-studio/src/Http/Controllers/EmailProviderWebhookController.php`
- Create: `packages/email-studio/src/Http/Controllers/TrackEmailClickController.php`
- Create: `packages/email-studio/src/Http/Controllers/TrackEmailOpenController.php`
- Create: `packages/email-studio/routes/web.php`
- Create: `packages/email-studio/tests/Feature/Http/EmailProviderWebhookControllerTest.php`
- Create: `packages/email-studio/tests/Feature/Http/EmailTrackingControllerTest.php`
- Create: `packages/email-studio/tests/Integration/Actions/RecordEmailReplyActionTest.php`
- Create: `packages/email-studio/tests/Integration/Actions/RedactExpiredEmailContentActionTest.php`

- [ ] **Step 1: Write failing tests**

Tests must prove:

- Webhooks are rejected when the provider secret/signature is invalid.
- Webhooks are resolved by an opaque profile endpoint token before payload trust or event processing.
- Delivery events update recipient status and timestamp idempotently.
- Duplicate provider events are ignored by a database unique key, not only Action logic.
- Bounce and complaint events create suppressions.
- Inbound replies are linked by provider message ID and recipient email.
- Click tracking redirects to the intended URL and records a click event.
- Open tracking returns a transparent pixel and records an open event.
- Tracking routes use opaque token strings and never expose recipient IDs or message IDs.
- Retention redacts rendered body snapshots, reply bodies, raw provider payloads, headers, sensitive context values, and stored clicked URLs while keeping operational metadata.

- [ ] **Step 2: Implement webhook route**

Routes:

```php
Route::prefix(trim((string) config('capell-email-studio.public_route_prefix'), '/'))
    ->post('/hooks/{profileEndpointToken}/{provider}', EmailProviderWebhookController::class)
    ->middleware(['throttle:' . config('capell-email-studio.webhook_rate_limit')])
    ->name('capell.mail.hooks');
```

The `{profileEndpointToken}` value is an opaque token generated for an `EmailProfile` and stored hashed. The controller must resolve the profile from the token before verifying the provider signature with that profile's webhook secret.

- [ ] **Step 3: Implement signed tracking routes**

Routes:

```php
Route::prefix(trim((string) config('capell-email-studio.public_route_prefix'), '/'))
    ->get('/o/{trackingToken}', TrackEmailOpenController::class)
    ->middleware(['signed', 'throttle:' . config('capell-email-studio.tracking_rate_limit')])
    ->name('capell.mail.open');

Route::prefix(trim((string) config('capell-email-studio.public_route_prefix'), '/'))
    ->get('/c/{trackingToken}', TrackEmailClickController::class)
    ->middleware(['signed', 'throttle:' . config('capell-email-studio.tracking_rate_limit')])
    ->name('capell.mail.click');
```

The `{trackingToken}` value resolves through `email_tracking_tokens.token_hash`; route model binding to `EmailRecipient` is forbidden for public routes.

- [ ] **Step 4: Implement event and reply Actions**

`RecordEmailEventAction` maps provider event types to recipient status transitions and writes a deterministic idempotency key before updating recipient state. `RecordEmailReplyAction` creates the reply, marks the recipient replied, and records timeline metadata.

- [ ] **Step 5: Implement retention**

`RedactExpiredEmailContentAction` nulls `rendered_html`, `rendered_text`, reply bodies, raw provider payloads, headers, sensitive context keys, and stored clicked URLs for records older than `body_retention_days`. It must leave IDs, status, timestamps, event type, provider message IDs, and non-sensitive diagnostics intact.

- [ ] **Step 6: Verify**

Run:

```bash
vendor/bin/pest packages/email-studio/tests/Feature/Http/EmailProviderWebhookControllerTest.php packages/email-studio/tests/Feature/Http/EmailTrackingControllerTest.php packages/email-studio/tests/Integration/Actions/RecordEmailReplyActionTest.php packages/email-studio/tests/Integration/Actions/RedactExpiredEmailContentActionTest.php --configuration=phpunit.xml
```

Expected: PASS.

- [ ] **Step 7: Commit**

Run:

```bash
git add packages/email-studio
git commit -m "feat: record email provider events and replies"
```

## Task 6: Filament Admin, Settings, And Diagnostics

**Files:**

- Create: `packages/email-studio/src/Enums/ResourceEnum.php`
- Create: `packages/email-studio/src/Filament/Resources/EmailTemplates/**`
- Create: `packages/email-studio/src/Filament/Resources/EmailProfiles/**`
- Create: `packages/email-studio/src/Filament/Resources/EmailMessages/**`
- Create: `packages/email-studio/src/Filament/Resources/EmailRecipients/**`
- Create: `packages/email-studio/src/Filament/Resources/EmailReplies/**`
- Create: `packages/email-studio/src/Filament/Resources/EmailSuppressions/**`
- Create: `packages/email-studio/src/Filament/Resources/EmailEvents/**`
- Create: `packages/email-studio/src/Filament/Settings/EmailStudioSettingsSchema.php`
- Create: `packages/email-studio/src/Filament/Widgets/EmailStudioOverviewStatsWidget.php`
- Create: `packages/email-studio/resources/lang/en/navigation.php`
- Create: `packages/email-studio/resources/lang/en/form.php`
- Create: `packages/email-studio/resources/lang/en/table.php`
- Create: `packages/email-studio/resources/lang/en/actions.php`
- Create: `packages/email-studio/resources/lang/en/messages.php`
- Create: `packages/email-studio/resources/lang/en/settings.php`
- Create: `packages/email-studio/resources/lang/en/widgets.php`
- Create: `packages/email-studio/tests/Feature/Filament/EmailStudioResourcesTest.php`
- Create: `packages/email-studio/tests/Feature/Filament/EmailStudioSettingsTest.php`

- [ ] **Step 1: Write failing Filament tests**

Tests should assert:

- Resources are contributed to the admin surface only when the package is installed.
- Navigation appears under Marketing or Operations with translated labels.
- Template create/edit forms can save a template and variant.
- Message resource is read-mostly and exposes timeline details.
- Settings schema includes default profile, retention days, tracking toggles, and webhook health copy.
- A user assigned to one site cannot list, view, edit, retry, reply-manage, or suppress records belonging only to another site.
- Global records are visible only to actors with global/package management permission.

- [ ] **Step 2: Implement `ResourceEnum`**

```php
enum ResourceEnum: string
{
    case EmailTemplates = \Capell\EmailStudio\Filament\Resources\EmailTemplates\EmailTemplateResource::class;
    case EmailProfiles = \Capell\EmailStudio\Filament\Resources\EmailProfiles\EmailProfileResource::class;
    case EmailMessages = \Capell\EmailStudio\Filament\Resources\EmailMessages\EmailMessageResource::class;
    case EmailRecipients = \Capell\EmailStudio\Filament\Resources\EmailRecipients\EmailRecipientResource::class;
    case EmailReplies = \Capell\EmailStudio\Filament\Resources\EmailReplies\EmailReplyResource::class;
    case EmailSuppressions = \Capell\EmailStudio\Filament\Resources\EmailSuppressions\EmailSuppressionResource::class;
    case EmailEvents = \Capell\EmailStudio\Filament\Resources\EmailEvents\EmailEventResource::class;
}
```

- [ ] **Step 3: Register admin resources and settings**

Follow Newsletter's `AdminServiceProvider` pattern:

```php
CapellAdmin::registerNavigationGroup(
    label: 'capell-admin::navigation.group_marketing',
    position: NavigationGroupPositionEnum::After,
    relativeTo: 'capell-admin::navigation.group_content',
);
```

Then loop through `ResourceEnum::cases()` and call `CapellAdmin::contributeToAdminSurface(...)`. Each resource query must apply site scoping before table filters run. Follow Newsletter's site-scope concern or create an Email Studio-specific concern if the resource query shape needs message/recipient joins.

- [ ] **Step 4: Build resources**

Keep resource behavior thin. Any preview, test send, approval, suppression, retry, reply assignment, or resend behavior must call Actions. Tables should prioritize operational scanning: status badge, subject, recipient count, latest event, provider, profile, site, created time. Do not expose provider secrets, webhook tokens, raw payloads, or rendered body snapshots to actors without explicit diagnostic permission.

- [ ] **Step 5: Verify**

Run:

```bash
vendor/bin/pest packages/email-studio/tests/Feature/Filament/EmailStudioResourcesTest.php packages/email-studio/tests/Feature/Filament/EmailStudioSettingsTest.php --configuration=phpunit.xml
```

Expected: PASS.

- [ ] **Step 6: Commit**

Run:

```bash
git add packages/email-studio
git commit -m "feat: add email studio admin"
```

## Task 7: FormBuilder, Newsletter, And CampaignStudio Integrations

**Files:**

- Create: `packages/email-studio/src/Listeners/SendFormSubmissionEmails.php`
- Create: `packages/email-studio/src/Contracts/EmailSuppressionProvider.php`
- Create: `packages/email-studio/src/Support/NewsletterEmailSuppressionProvider.php`
- Create: `packages/email-studio/src/Support/CampaignEmailLinkDecorator.php`
- Create: `packages/email-studio/tests/Integration/Listeners/FormSubmissionEmailTest.php`
- Create: `packages/email-studio/tests/Integration/Support/NewsletterSuppressionProviderTest.php`
- Create: `packages/email-studio/tests/Integration/Support/CampaignEmailLinkDecoratorTest.php`

- [ ] **Step 1: Write failing integration tests**

Tests should assert:

- When FormBuilder is installed, form submission events can send admin and confirmation emails through Email Studio templates.
- When Newsletter is installed, unsubscribed/suppressed subscribers prevent outbound sends.
- When CampaignStudio is installed, tracked links receive campaign UTM values without changing non-HTTP links.
- When optional packages are absent, Email Studio still boots and sends transactional email.
- When optional packages are absent, Email Studio does not autoload optional package classes or resolve optional package services.

- [ ] **Step 2: Implement optional class checks**

Use the Newsletter pattern:

```php
$formSubmittedEvent = implode('\\', ['Capell', 'FormBuilder', 'Events', 'FormSubmitted']);

if (class_exists($formSubmittedEvent)) {
    Event::listen($formSubmittedEvent, SendFormSubmissionEmails::class);
}
```

- [ ] **Step 3: Implement integrations behind contracts**

Do not import optional package models directly in core send Actions. Resolve optional providers from the container and no-op cleanly when they are not bound. Optional integration classes may reference optional package types only inside listeners/providers guarded by `class_exists(...)`.

- [ ] **Step 4: Verify**

Run:

```bash
vendor/bin/pest packages/email-studio/tests/Integration/Listeners/FormSubmissionEmailTest.php packages/email-studio/tests/Integration/Support/NewsletterSuppressionProviderTest.php packages/email-studio/tests/Integration/Support/CampaignEmailLinkDecoratorTest.php --configuration=phpunit.xml
```

Expected: PASS.

- [ ] **Step 5: Commit**

Run:

```bash
git add packages/email-studio
git commit -m "feat: integrate email studio with capell packages"
```

## Task 8: Documentation, Product Positioning, And Final Verification

**Files:**

- Create: `packages/email-studio/README.md`
- Create: `packages/email-studio/docs/overview.md`
- Create: `packages/email-studio/docs/email-studio-api.md`
- Create: `packages/email-studio/docs/email-studio-database.md`
- Create: `packages/email-studio/docs/screenshots.json`
- Modify: `README.md`
- Modify: `docs/product-groups.md`
- Create: `packages/email-studio/tests/Arch/EmailStudioBoundaryTest.php`

- [ ] **Step 1: Write boundary tests**

Tests should assert:

- Core/Admin/Frontend packages do not import `Capell\EmailStudio`.
- Email Studio public routes do not render authoring metadata.
- Public routes use opaque tokens and do not expose package names, recipient IDs, message IDs, template keys, admin URLs, or provider secrets.
- Actions do not live in Filament resources.
- No model method performs writes with side effects.

- [ ] **Step 2: Write package docs**

Docs must cover:

- What Email Studio is and is not.
- Installation command.
- Sending email from code with `SendEmailAction`.
- Registering package-owned templates.
- Provider setup, opaque webhook endpoint tokens, signature verification, and webhook health.
- Reply capture.
- Suppression rules.
- Retention/redaction.
- Optional integrations with FormBuilder, Newsletter, CampaignStudio.
- Testing with `FakeEmailProviderAdapter`.

- [ ] **Step 3: Update product positioning**

Put Email Studio in a premium "Capell Operations" or new "Capell Communications" group. Recommended product copy:

> Email Studio gives site owners a reliable transactional email command centre: editable templates, sender profiles, delivery logs, provider events, inbound replies, suppressions, and diagnostics for every important email Capell sends.

- [ ] **Step 4: Run focused package tests**

Run:

```bash
vendor/bin/pest packages/email-studio/tests --configuration=phpunit.xml
```

Expected: PASS.

- [ ] **Step 5: Run package repo preflight**

Run:

```bash
composer preflight
```

Expected: PASS.

- [ ] **Step 6: Commit docs and final verification fixes**

Run:

```bash
git add README.md docs/product-groups.md packages/email-studio
git commit -m "docs: document email studio package"
```

## Implementation Order

1. Package skeleton.
2. Database/model layer.
3. Template rendering.
4. Sending pipeline.
5. Provider events and replies.
6. Admin surface.
7. Optional package integrations.
8. Docs and final verification.

This order keeps the first useful software narrow: by Task 4, Capell can send audited transactional email from templates through fake, SMTP, and Postmark providers. The later tasks make it sellable.

## Risk Notes

- Provider APIs differ heavily. Keep the v1 adapter contract small and normalize inbound data at the boundary.
- Tracking pixels and click redirects can create privacy concerns. Make tracking configurable per profile and keep retention redaction enabled by default.
- Template rendering must stay deliberately limited. A simple variable renderer is safer than evaluating Blade or arbitrary expressions from admin-editable content.
- Public tracking routes must expose only opaque signed tokens. They must not expose template keys, model IDs, admin URLs, package names, or package internals.
- Suppression checks must happen before queueing delivery and again immediately before provider handoff so queued retries do not send to newly suppressed recipients.
- Webhooks must resolve an `EmailProfile` from an opaque endpoint token before verifying signatures. Never trust provider payload contents to choose the profile.
- Provider event idempotency must be enforced with database unique keys because providers retry and jobs may race.
- Multi-site scoping is privacy-sensitive. Every admin resource and Action must carry `site_id`/`site_scope_key` deliberately.
- Reply handling depends on provider support and DNS setup. The admin should show webhook health and last inbound event rather than implying automatic setup.

## Expert Review Checklist

- The package has a clear commercial boundary: transactional email infrastructure, not a second newsletter package.
- The data model separates content (`EmailTemplate`), context/version (`EmailTemplateVariant`), delivery config (`EmailProfile`), send record (`EmailMessage`), recipient state (`EmailRecipient`), provider lifecycle (`EmailEvent`), inbound response (`EmailReply`), suppression (`EmailSuppression`), and public tracking (`EmailTrackingToken`).
- All business behavior is in Actions, not resources/controllers/models.
- Optional integrations use class existence checks/contracts and do not hard-require Newsletter, FormBuilder, or CampaignStudio.
- The MVP is testable without real provider credentials.
- Public tracking and webhook routes avoid frontend authoring leakage, package internals, and raw IDs.
- Site scoping and admin authorization are specified and tested.
- Suppression and provider event idempotency are safe under queued jobs and webhook retries.
- Documentation explains installation, sending, template registration, provider setup, reply capture, and retention.
