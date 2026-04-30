# Forms Package Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the existing `capell-app/forms` stub into a small Capell package for editor-managed forms, Livewire-backed frontend submission, stored submissions, and an admin submission inbox, then add a separate Newsletter package that depends on Forms and Mosaic for the newsletter signup widget.

**Architecture:** Keep all Forms behavior in Actions, all cross-layer structures in Spatie Data objects, all persisted option values in enums, and all admin UI in the established Filament Resource/Schemas/Tables/Pages split. The Forms frontend surface is a Blade component that mounts one package Livewire component; projects customise markup by overriding views and customise side effects by listening to `FormSubmitted`. Mosaic-specific form experiences live in dependent packages, starting with `capell-app/newsletter`, so Forms does not depend on Mosaic.

**Tech Stack:** PHP 8.2, Laravel, Capell Core/Admin/Frontend, Filament, Livewire, Spatie Laravel Data, Lorisleiva Actions, Pest.

---

## File Structure

### Modify

- `packages/forms/composer.json` - move factory autoloading into package `autoload`, keep package dependencies lean.
- `packages/forms/database/migrations/create_forms_table.php` - add handle, schema, settings, active state, site scoped uniqueness.
- `packages/forms/database/migrations/create_submissions_table.php` - add site, payload, meta, status, submitted timestamp indexes.
- `packages/forms/src/Models/Form.php` - casts, relationships, scopes, factory, fillable.
- `packages/forms/src/Models/Submission.php` - casts, relationships, status scopes, factory, fillable.
- `packages/forms/src/Providers/FormsServiceProvider.php` - package metadata, translations, views, resources, Livewire, Blade components.
- `packages/forms/tests/FormsTestCase.php` - load migrations and Livewire package support needed by the new tests.
- `packages/forms/README.md` - document install, usage, admin areas, extension points.

### Create

- `packages/forms/config/capell-forms.php`
- `packages/forms/database/factories/FormFactory.php`
- `packages/forms/database/factories/SubmissionFactory.php`
- `packages/forms/resources/lang/en/form.php`
- `packages/forms/resources/lang/en/generic.php`
- `packages/forms/resources/lang/en/messages.php`
- `packages/forms/resources/lang/en/navigation.php`
- `packages/forms/resources/lang/en/package.php`
- `packages/forms/resources/lang/en/table.php`
- `packages/forms/resources/views/components/form.blade.php`
- `packages/forms/resources/views/livewire/form.blade.php`
- `packages/forms/resources/views/fields/checkbox.blade.php`
- `packages/forms/resources/views/fields/email.blade.php`
- `packages/forms/resources/views/fields/honeypot.blade.php`
- `packages/forms/resources/views/fields/hidden.blade.php`
- `packages/forms/resources/views/fields/select.blade.php`
- `packages/forms/resources/views/fields/text.blade.php`
- `packages/forms/resources/views/fields/textarea.blade.php`
- `packages/forms/src/Actions/ArchiveSubmissionAction.php`
- `packages/forms/src/Actions/BuildFormValidationRulesAction.php`
- `packages/forms/src/Actions/CreateSubmissionAction.php`
- `packages/forms/src/Actions/MarkSubmissionReadAction.php`
- `packages/forms/src/Actions/MarkSubmissionSpamAction.php`
- `packages/forms/src/Data/FormFieldData.php`
- `packages/forms/src/Data/FormSettingsData.php`
- `packages/forms/src/Data/SubmissionMetaData.php`
- `packages/forms/src/Data/SubmissionPayloadData.php`
- `packages/forms/src/Enums/FormFieldType.php`
- `packages/forms/src/Enums/LivewireComponentEnum.php`
- `packages/forms/src/Enums/ResourceEnum.php`
- `packages/forms/src/Enums/SubmissionStatus.php`
- `packages/forms/src/Events/FormSubmitted.php`
- `packages/forms/src/Filament/Resources/Forms/FormResource.php`
- `packages/forms/src/Filament/Resources/Forms/Pages/ManageForms.php`
- `packages/forms/src/Filament/Resources/Forms/Schemas/FormForm.php`
- `packages/forms/src/Filament/Resources/Forms/Tables/FormsTable.php`
- `packages/forms/src/Filament/Resources/Submissions/Pages/ListSubmissions.php`
- `packages/forms/src/Filament/Resources/Submissions/Pages/ViewSubmission.php`
- `packages/forms/src/Filament/Resources/Submissions/SubmissionResource.php`
- `packages/forms/src/Filament/Resources/Submissions/Schemas/SubmissionInfolist.php`
- `packages/forms/src/Filament/Resources/Submissions/Tables/SubmissionsTable.php`
- `packages/forms/src/Livewire/FormComponent.php`
- `packages/forms/src/View/Components/Form.php`
- `packages/forms/tests/Feature/Filament/FormResourceTest.php`
- `packages/forms/tests/Feature/Filament/SubmissionResourceTest.php`
- `packages/forms/tests/Feature/Livewire/FormComponentTest.php`
- `packages/forms/tests/Integration/Actions/CreateSubmissionActionTest.php`
- `packages/forms/tests/Integration/Actions/SubmissionStatusActionsTest.php`
- `packages/forms/tests/Integration/Models/FormModelTest.php`
- `packages/forms/tests/Integration/Models/SubmissionModelTest.php`
- `packages/forms/tests/Unit/Actions/BuildFormValidationRulesActionTest.php`
- `packages/forms/tests/Unit/Data/FormFieldDataTest.php`
- `packages/newsletter/composer.json`
- `packages/newsletter/capell.json`
- `packages/newsletter/resources/lang/en/generic.php`
- `packages/newsletter/resources/lang/en/package.php`
- `packages/newsletter/resources/views/components/widget/newsletter-signup.blade.php`
- `packages/newsletter/src/Actions/CreateNewsletterSignupFormAction.php`
- `packages/newsletter/src/Actions/CreateNewsletterSignupWidgetAction.php`
- `packages/newsletter/src/Events/NewsletterSignupSubmitted.php`
- `packages/newsletter/src/Filament/Configurators/Widgets/NewsletterSignupWidgetConfigurator.php`
- `packages/newsletter/src/Listeners/DispatchNewsletterSignupSubmittedListener.php`
- `packages/newsletter/src/Providers/NewsletterServiceProvider.php`
- `packages/newsletter/tests/NewsletterTestCase.php`
- `packages/newsletter/tests/Integration/CreateNewsletterSignupWidgetActionTest.php`
- `packages/newsletter/tests/Unit/ManifestRequirementsTest.php`

---

## Task 1: Data Objects And Enums

**Files:**

- Create: `packages/forms/src/Enums/FormFieldType.php`
- Create: `packages/forms/src/Enums/SubmissionStatus.php`
- Create: `packages/forms/src/Data/FormFieldData.php`
- Create: `packages/forms/src/Data/FormSettingsData.php`
- Create: `packages/forms/src/Data/SubmissionPayloadData.php`
- Create: `packages/forms/src/Data/SubmissionMetaData.php`
- Create: `packages/forms/tests/Unit/Data/FormFieldDataTest.php`

- [ ] **Step 1: Write enum and data tests**

Create `packages/forms/tests/Unit/Data/FormFieldDataTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Forms\Data\FormFieldData;
use Capell\Forms\Data\FormSettingsData;
use Capell\Forms\Data\SubmissionPayloadData;
use Capell\Forms\Enums\FormFieldType;

it('creates form field data from editor state', function (): void {
    $field = FormFieldData::from([
        'key' => 'email',
        'label' => 'Email address',
        'type' => 'email',
        'required' => true,
        'placeholder' => 'you@example.com',
        'help_text' => 'Used to reply to your enquiry.',
        'options' => [],
        'default_value' => null,
        'validation_rules' => ['email'],
    ]);

    expect($field->key)->toBe('email')
        ->and($field->type)->toBe(FormFieldType::Email)
        ->and($field->required)->toBeTrue()
        ->and($field->validationRules)->toBe(['email']);
});

it('provides simple default form settings', function (): void {
    $settings = FormSettingsData::from([]);

    expect($settings->successMessage)->toBeNull()
        ->and($settings->storeSubmissions)->toBeTrue()
        ->and($settings->notificationEmail)->toBeNull()
        ->and($settings->collectIpAddress)->toBeTrue()
        ->and($settings->collectUserAgent)->toBeTrue();
});

it('wraps submitted values in payload data', function (): void {
    $payload = SubmissionPayloadData::from([
        'values' => [
            'name' => 'Ben',
            'email' => 'ben@example.com',
        ],
    ]);

    expect($payload->values)->toBe([
        'name' => 'Ben',
        'email' => 'ben@example.com',
    ]);
});
```

- [ ] **Step 2: Run the data tests and verify failure**

Run:

```bash
vendor/bin/pest packages/forms/tests/Unit/Data/FormFieldDataTest.php
```

Expected: fail because the data and enum classes do not exist.

- [ ] **Step 3: Add field type enum**

Create `packages/forms/src/Enums/FormFieldType.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Enums;

use Filament\Support\Contracts\HasLabel;

enum FormFieldType: string implements HasLabel
{
    case Text = 'text';
    case Email = 'email';
    case Textarea = 'textarea';
    case Select = 'select';
    case Checkbox = 'checkbox';
    case Hidden = 'hidden';
    case Honeypot = 'honeypot';

    public function getLabel(): string
    {
        return match ($this) {
            self::Text => __('capell-forms::form.field_type.text'),
            self::Email => __('capell-forms::form.field_type.email'),
            self::Textarea => __('capell-forms::form.field_type.textarea'),
            self::Select => __('capell-forms::form.field_type.select'),
            self::Checkbox => __('capell-forms::form.field_type.checkbox'),
            self::Hidden => __('capell-forms::form.field_type.hidden'),
            self::Honeypot => __('capell-forms::form.field_type.honeypot'),
        };
    }

    public function isStoredInPayload(): bool
    {
        return $this !== self::Honeypot;
    }
}
```

- [ ] **Step 4: Add submission status enum**

Create `packages/forms/src/Enums/SubmissionStatus.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum SubmissionStatus: string implements HasColor, HasIcon, HasLabel
{
    case New = 'new';
    case Read = 'read';
    case Archived = 'archived';
    case Spam = 'spam';

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Read => 'gray',
            self::Archived => 'warning',
            self::Spam => 'danger',
        };
    }

    public function getIcon(): string|Heroicon
    {
        return match ($this) {
            self::New => Heroicon::OutlinedInbox,
            self::Read => Heroicon::OutlinedEnvelopeOpen,
            self::Archived => Heroicon::OutlinedArchiveBox,
            self::Spam => Heroicon::OutlinedNoSymbol,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::New => __('capell-forms::generic.submission_status.new'),
            self::Read => __('capell-forms::generic.submission_status.read'),
            self::Archived => __('capell-forms::generic.submission_status.archived'),
            self::Spam => __('capell-forms::generic.submission_status.spam'),
        };
    }
}
```

- [ ] **Step 5: Add data objects**

Create `packages/forms/src/Data/FormFieldData.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Data;

use Capell\Forms\Enums\FormFieldType;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class FormFieldData extends Data
{
    /**
     * @param  array<string, string>  $options
     * @param  array<int, string>  $validationRules
     */
    public function __construct(
        public string $key,
        public string $label,
        public FormFieldType $type = FormFieldType::Text,
        public bool $required = false,
        public ?string $placeholder = null,
        public ?string $helpText = null,
        public array $options = [],
        public mixed $defaultValue = null,
        public array $validationRules = [],
    ) {}
}
```

Create `packages/forms/src/Data/FormSettingsData.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class FormSettingsData extends Data
{
    public function __construct(
        public ?string $successMessage = null,
        public bool $storeSubmissions = true,
        public ?string $notificationEmail = null,
        public bool $collectIpAddress = true,
        public bool $collectUserAgent = true,
    ) {}
}
```

Create `packages/forms/src/Data/SubmissionPayloadData.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Data;

use Spatie\LaravelData\Data;

class SubmissionPayloadData extends Data
{
    /**
     * @param  array<string, mixed>  $values
     */
    public function __construct(
        public array $values = [],
    ) {}
}
```

Create `packages/forms/src/Data/SubmissionMetaData.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class SubmissionMetaData extends Data
{
    public function __construct(
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?string $url = null,
        public ?string $referer = null,
    ) {}
}
```

- [ ] **Step 6: Run data tests**

Run:

```bash
vendor/bin/pest packages/forms/tests/Unit/Data/FormFieldDataTest.php
```

Expected: pass.

- [ ] **Step 7: Commit**

```bash
git add packages/forms/src/Enums/FormFieldType.php packages/forms/src/Enums/SubmissionStatus.php packages/forms/src/Data/FormFieldData.php packages/forms/src/Data/FormSettingsData.php packages/forms/src/Data/SubmissionPayloadData.php packages/forms/src/Data/SubmissionMetaData.php packages/forms/tests/Unit/Data/FormFieldDataTest.php
git commit -m "feat: add forms data objects"
```

---

## Task 2: Database, Models, And Factories

**Files:**

- Modify: `packages/forms/composer.json`
- Modify: `packages/forms/database/migrations/create_forms_table.php`
- Modify: `packages/forms/database/migrations/create_submissions_table.php`
- Modify: `packages/forms/src/Models/Form.php`
- Modify: `packages/forms/src/Models/Submission.php`
- Create: `packages/forms/database/factories/FormFactory.php`
- Create: `packages/forms/database/factories/SubmissionFactory.php`
- Create: `packages/forms/tests/Integration/Models/FormModelTest.php`
- Create: `packages/forms/tests/Integration/Models/SubmissionModelTest.php`

- [ ] **Step 1: Write model tests**

Create `packages/forms/tests/Integration/Models/FormModelTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Forms\Data\FormFieldData;
use Capell\Forms\Data\FormSettingsData;
use Capell\Forms\Enums\FormFieldType;
use Capell\Forms\Models\Form;
use Capell\Forms\Models\Submission;

it('casts schema and settings to structured data', function (): void {
    $form = Form::factory()->create([
        'schema' => [
            [
                'key' => 'email',
                'label' => 'Email',
                'type' => 'email',
                'required' => true,
            ],
        ],
        'settings' => [
            'success_message' => 'Thanks.',
            'store_submissions' => true,
        ],
    ]);

    $form->refresh();

    expect($form->schema)->toHaveCount(1)
        ->and($form->schema->first())->toBeInstanceOf(FormFieldData::class)
        ->and($form->schema->first()->type)->toBe(FormFieldType::Email)
        ->and($form->settings)->toBeInstanceOf(FormSettingsData::class)
        ->and($form->settings->successMessage)->toBe('Thanks.');
});

it('has submissions', function (): void {
    $form = Form::factory()->create();
    $submission = Submission::factory()->for($form)->create();

    expect($form->submissions()->pluck('id')->all())->toBe([$submission->getKey()]);
});

it('scopes active forms', function (): void {
    Form::factory()->create(['handle' => 'enabled', 'is_active' => true]);
    Form::factory()->create(['handle' => 'disabled', 'is_active' => false]);

    expect(Form::query()->active()->pluck('handle')->all())->toBe(['enabled']);
});
```

Create `packages/forms/tests/Integration/Models/SubmissionModelTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Forms\Data\SubmissionMetaData;
use Capell\Forms\Data\SubmissionPayloadData;
use Capell\Forms\Enums\SubmissionStatus;
use Capell\Forms\Models\Form;
use Capell\Forms\Models\Submission;

it('casts payload, meta, status, and submitted timestamp', function (): void {
    $submission = Submission::factory()->create([
        'payload' => ['values' => ['email' => 'ben@example.com']],
        'meta' => ['ip_address' => '127.0.0.1'],
        'status' => 'read',
        'submitted_at' => now(),
    ]);

    $submission->refresh();

    expect($submission->payload)->toBeInstanceOf(SubmissionPayloadData::class)
        ->and($submission->payload->values)->toBe(['email' => 'ben@example.com'])
        ->and($submission->meta)->toBeInstanceOf(SubmissionMetaData::class)
        ->and($submission->meta->ipAddress)->toBe('127.0.0.1')
        ->and($submission->status)->toBe(SubmissionStatus::Read)
        ->and($submission->submitted_at)->not->toBeNull();
});

it('belongs to a form', function (): void {
    $form = Form::factory()->create();
    $submission = Submission::factory()->for($form)->create();

    expect($submission->form->is($form))->toBeTrue();
});
```

- [ ] **Step 2: Run model tests and verify failure**

Run:

```bash
vendor/bin/pest packages/forms/tests/Integration/Models
```

Expected: fail because factories, columns, casts, and scopes are missing.

- [ ] **Step 3: Update composer factory autoload**

Modify `packages/forms/composer.json` so factories match other Capell packages:

```json
"autoload": {
    "psr-4": {
        "Capell\\Forms\\": "src/",
        "Capell\\Forms\\Database\\Factories\\": "database/factories"
    }
},
"autoload-dev": {
    "psr-4": {
        "Capell\\Forms\\Tests\\": "tests/"
    }
}
```

Run:

```bash
composer dump-autoload
```

Expected: autoload files regenerate successfully.

- [ ] **Step 4: Update migrations**

Replace `packages/forms/database/migrations/create_forms_table.php` with:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('name');
            $table->string('handle');
            $table->text('description')->nullable();
            $table->json('schema')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['site_id', 'handle']);
            $table->index('site_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
```

Replace `packages/forms/database/migrations/create_submissions_table.php` with:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->longText('payload')->nullable();
            $table->longText('meta')->nullable();
            $table->string('status')->default('new');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index('form_id');
            $table->index('site_id');
            $table->index('status');
            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
```

- [ ] **Step 5: Update models**

Update `packages/forms/src/Models/Form.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Models;

use Capell\Core\Models\Site;
use Capell\Forms\Data\FormFieldData;
use Capell\Forms\Data\FormSettingsData;
use Capell\Forms\Database\Factories\FormFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\LaravelData\DataCollection;

class Form extends Model
{
    /** @use HasFactory<FormFactory> */
    use HasFactory;

    protected $fillable = [
        'site_id',
        'name',
        'handle',
        'description',
        'schema',
        'settings',
        'is_active',
    ];

    protected static string $factory = FormFactory::class;

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    protected function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected function casts(): array
    {
        return [
            'schema' => DataCollection::class . ':' . FormFieldData::class,
            'settings' => FormSettingsData::class,
            'is_active' => 'boolean',
        ];
    }
}
```

Update `packages/forms/src/Models/Submission.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Models;

use Capell\Core\Models\Site;
use Capell\Forms\Casts\EncryptedDataCast;
use Capell\Forms\Data\SubmissionMetaData;
use Capell\Forms\Data\SubmissionPayloadData;
use Capell\Forms\Database\Factories\SubmissionFactory;
use Capell\Forms\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Submission extends Model
{
    /** @use HasFactory<SubmissionFactory> */
    use HasFactory;

    protected $fillable = [
        'form_id',
        'payload',
        'meta',
        'status',
        'submitted_at',
    ];

    protected static string $factory = SubmissionFactory::class;

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    protected function casts(): array
    {
        return [
            'payload' => EncryptedDataCast::class . ':' . SubmissionPayloadData::class,
            'meta' => EncryptedDataCast::class . ':' . SubmissionMetaData::class,
            'status' => SubmissionStatus::class,
            'submitted_at' => 'datetime',
        ];
    }
}
```

- [ ] **Step 6: Add factories**

Create `packages/forms/database/factories/FormFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Database\Factories;

use Capell\Core\Database\Factories\SiteFactory;
use Capell\Core\Models\Site;
use Capell\Forms\Models\Form;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Form>
 */
class FormFactory extends Factory
{
    protected $model = Form::class;

    public function definition(): array
    {
        $handle = $this->faker->unique()->slug(2);

        return [
            'site_id' => fn (): SiteFactory => Site::factory()->withTranslations(),
            'name' => Str::headline($handle),
            'handle' => Str::slug($handle),
            'description' => $this->faker->sentence(),
            'schema' => [
                [
                    'key' => 'email',
                    'label' => 'Email',
                    'type' => 'email',
                    'required' => true,
                    'validation_rules' => ['email'],
                ],
            ],
            'settings' => [
                'success_message' => null,
                'store_submissions' => true,
                'notification_email' => null,
                'collect_ip_address' => true,
                'collect_user_agent' => true,
            ],
            'is_active' => true,
        ];
    }
}
```

Create `packages/forms/database/factories/SubmissionFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Database\Factories;

use Capell\Core\Models\Site;
use Capell\Forms\Enums\SubmissionStatus;
use Capell\Forms\Models\Form;
use Capell\Forms\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    protected $model = Submission::class;

    public function configure(): static
    {
        return $this->afterMaking(function (Submission $submission): void {
            if ($submission->site_id !== null) {
                return;
            }

            if ($submission->relationLoaded('form') && $submission->form !== null) {
                $submission->site_id = $submission->form->site_id;

                return;
            }

            if ($submission->form_id !== null) {
                $submission->site_id = Form::query()->findOrFail($submission->form_id)->site_id;
            }
        });
    }

    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'site_id' => fn (array $attributes): int => Form::query()->findOrFail($attributes['form_id'])->site_id,
            'payload' => [
                'values' => [
                    'email' => $this->faker->safeEmail(),
                ],
            ],
            'meta' => [
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Forms test agent',
                'url' => 'https://example.test/contact',
                'referer' => null,
            ],
            'status' => SubmissionStatus::New,
            'submitted_at' => now(),
        ];
    }

    public function site(Site $site): static
    {
        return $this->for(Form::factory()->for($site));
    }
}
```

- [ ] **Step 7: Run model tests**

Run:

```bash
vendor/bin/pest packages/forms/tests/Integration/Models
```

Expected: pass.

- [ ] **Step 8: Commit**

```bash
git add packages/forms/composer.json packages/forms/database/migrations/create_forms_table.php packages/forms/database/migrations/create_submissions_table.php packages/forms/src/Models/Form.php packages/forms/src/Models/Submission.php packages/forms/database/factories/FormFactory.php packages/forms/database/factories/SubmissionFactory.php packages/forms/tests/Integration/Models/FormModelTest.php packages/forms/tests/Integration/Models/SubmissionModelTest.php
git commit -m "feat: add forms persistence model"
```

---

## Task 3: Validation And Submission Actions

**Files:**

- Create: `packages/forms/src/Actions/BuildFormValidationRulesAction.php`
- Create: `packages/forms/src/Actions/CreateSubmissionAction.php`
- Create: `packages/forms/src/Events/FormSubmitted.php`
- Create: `packages/forms/tests/Unit/Actions/BuildFormValidationRulesActionTest.php`
- Create: `packages/forms/tests/Integration/Actions/CreateSubmissionActionTest.php`

- [ ] **Step 1: Write validation action tests**

Create `packages/forms/tests/Unit/Actions/BuildFormValidationRulesActionTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Forms\Actions\BuildFormValidationRulesAction;
use Capell\Forms\Models\Form;

it('builds validation rules from field data', function (): void {
    $form = Form::factory()->make([
        'schema' => [
            [
                'key' => 'name',
                'label' => 'Name',
                'type' => 'text',
                'required' => true,
                'validation_rules' => ['max:120'],
            ],
            [
                'key' => 'email',
                'label' => 'Email',
                'type' => 'email',
                'required' => true,
                'validation_rules' => ['email'],
            ],
            [
                'key' => 'company_website',
                'label' => 'Company website',
                'type' => 'honeypot',
                'required' => false,
            ],
        ],
    ]);

    expect(BuildFormValidationRulesAction::run($form))->toBe([
        'name' => ['required', 'string', 'max:120'],
        'email' => ['required', 'email'],
        'company_website' => ['nullable', 'prohibited'],
    ]);
});

it('ignores unsupported editor validation rules', function (): void {
    $form = Form::factory()->make([
        'schema' => [
            [
                'key' => 'message',
                'label' => 'Message',
                'type' => 'textarea',
                'required' => false,
                'validation_rules' => ['max:500', 'starts_with:<?php'],
            ],
        ],
    ]);

    expect(BuildFormValidationRulesAction::run($form))->toBe([
        'message' => ['nullable', 'string', 'max:500'],
    ]);
});
```

- [ ] **Step 2: Write submission action tests**

Create `packages/forms/tests/Integration/Actions/CreateSubmissionActionTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Forms\Actions\CreateSubmissionAction;
use Capell\Forms\Data\SubmissionMetaData;
use Capell\Forms\Enums\SubmissionStatus;
use Capell\Forms\Events\FormSubmitted;
use Capell\Forms\Models\Form;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

it('validates and stores a submission', function (): void {
    Event::fake([FormSubmitted::class]);

    $form = Form::factory()->create([
        'schema' => [
            [
                'key' => 'email',
                'label' => 'Email',
                'type' => 'email',
                'required' => true,
                'validation_rules' => ['email'],
            ],
        ],
    ]);

    $submission = CreateSubmissionAction::run(
        form: $form,
        input: ['email' => 'ben@example.com'],
        meta: new SubmissionMetaData(ipAddress: '127.0.0.1', userAgent: 'Pest'),
    );

    expect($submission->exists)->toBeTrue()
        ->and($submission->form->is($form))->toBeTrue()
        ->and($submission->payload->values)->toBe(['email' => 'ben@example.com'])
        ->and($submission->status)->toBe(SubmissionStatus::New);

    Event::assertDispatched(FormSubmitted::class);
});

it('does not store honeypot values in payload', function (): void {
    $form = Form::factory()->create([
        'schema' => [
            ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'validation_rules' => ['email']],
            ['key' => 'company_website', 'label' => 'Company website', 'type' => 'honeypot', 'required' => false],
        ],
    ]);

    $submission = CreateSubmissionAction::run(
        form: $form,
        input: ['email' => 'ben@example.com', 'company_website' => null],
        meta: new SubmissionMetaData(),
    );

    expect($submission->payload->values)->toBe(['email' => 'ben@example.com']);
});

it('throws a validation exception for invalid data', function (): void {
    $form = Form::factory()->create([
        'schema' => [
            ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'validation_rules' => ['email']],
        ],
    ]);

    CreateSubmissionAction::run(
        form: $form,
        input: ['email' => 'not-an-email'],
        meta: new SubmissionMetaData(),
    );
})->throws(ValidationException::class);
```

- [ ] **Step 3: Run action tests and verify failure**

Run:

```bash
vendor/bin/pest packages/forms/tests/Unit/Actions/BuildFormValidationRulesActionTest.php packages/forms/tests/Integration/Actions/CreateSubmissionActionTest.php
```

Expected: fail because actions and event do not exist.

- [ ] **Step 4: Add validation action**

Create `packages/forms/src/Actions/BuildFormValidationRulesAction.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Actions;

use Capell\Forms\Data\FormFieldData;
use Capell\Forms\Enums\FormFieldType;
use Capell\Forms\Models\Form;
use Lorisleiva\Actions\Concerns\AsAction;

class BuildFormValidationRulesAction
{
    use AsAction;

    /**
     * @return array<string, array<int, string>>
     */
    public function handle(Form $form): array
    {
        $rules = [];

        foreach ($form->schema as $field) {
            /** @var FormFieldData $field */
            $rules[$field->key] = $this->rulesForField($field);
        }

        return $rules;
    }

    /**
     * @return array<int, string>
     */
    private function rulesForField(FormFieldData $field): array
    {
        if ($field->type === FormFieldType::Honeypot) {
            return ['nullable', 'prohibited'];
        }

        $rules = [$field->required ? 'required' : 'nullable'];

        if (in_array($field->type, [FormFieldType::Text, FormFieldType::Textarea, FormFieldType::Hidden], true)) {
            $rules[] = 'string';
        }

        if ($field->type === FormFieldType::Email) {
            $rules[] = 'email';
        }

        if ($field->type === FormFieldType::Select) {
            $rules[] = 'string';
            $rules[] = 'in:' . implode(',', array_keys($field->options));
        }

        if ($field->type === FormFieldType::Checkbox) {
            $rules[] = 'accepted';
        }

        return array_values(array_unique([
            ...$rules,
            ...$this->allowedEditorRules($field->validationRules),
        ]));
    }

    /**
     * @param  array<int, string>  $rules
     * @return array<int, string>
     */
    private function allowedEditorRules(array $rules): array
    {
        return array_values(array_filter(
            $rules,
            fn (string $rule): bool => preg_match('/^(min|max|size):[0-9]+$/', $rule) === 1
                || in_array($rule, ['email', 'url', 'alpha', 'alpha_dash', 'alpha_num'], true),
        ));
    }
}
```

- [ ] **Step 5: Add submitted event**

Create `packages/forms/src/Events/FormSubmitted.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Events;

use Capell\Forms\Models\Form;
use Capell\Forms\Models\Submission;
use Illuminate\Foundation\Events\Dispatchable;

class FormSubmitted
{
    use Dispatchable;

    public function __construct(
        public Form $form,
        public Submission $submission,
    ) {}
}
```

- [ ] **Step 6: Add create submission action**

Create `packages/forms/src/Actions/CreateSubmissionAction.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Actions;

use Capell\Forms\Data\FormFieldData;
use Capell\Forms\Data\SubmissionMetaData;
use Capell\Forms\Data\SubmissionPayloadData;
use Capell\Forms\Enums\SubmissionStatus;
use Capell\Forms\Events\FormSubmitted;
use Capell\Forms\Models\Form;
use Capell\Forms\Models\Submission;
use Illuminate\Support\Facades\Validator;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateSubmissionAction
{
    use AsAction;

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(Form $form, array $input, SubmissionMetaData $meta): Submission
    {
        $validated = Validator::make($input, BuildFormValidationRulesAction::run($form))->validate();

        $submission = Submission::query()->create([
            'form_id' => $form->getKey(),
            'site_id' => $form->site_id,
            'payload' => new SubmissionPayloadData($this->storedPayload($form, $validated)),
            'meta' => $meta,
            'status' => SubmissionStatus::New,
            'submitted_at' => now(),
        ]);

        FormSubmitted::dispatch($form, $submission);

        return $submission;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function storedPayload(Form $form, array $validated): array
    {
        $values = [];

        foreach ($form->schema as $field) {
            /** @var FormFieldData $field */
            if (! $field->type->isStoredInPayload()) {
                continue;
            }

            if (array_key_exists($field->key, $validated)) {
                $values[$field->key] = $validated[$field->key];
            }
        }

        return $values;
    }
}
```

- [ ] **Step 7: Run action tests**

Run:

```bash
vendor/bin/pest packages/forms/tests/Unit/Actions/BuildFormValidationRulesActionTest.php packages/forms/tests/Integration/Actions/CreateSubmissionActionTest.php
```

Expected: pass.

- [ ] **Step 8: Commit**

```bash
git add packages/forms/src/Actions/BuildFormValidationRulesAction.php packages/forms/src/Actions/CreateSubmissionAction.php packages/forms/src/Events/FormSubmitted.php packages/forms/tests/Unit/Actions/BuildFormValidationRulesActionTest.php packages/forms/tests/Integration/Actions/CreateSubmissionActionTest.php
git commit -m "feat: store form submissions"
```

---

## Task 4: Submission Status Actions

**Files:**

- Create: `packages/forms/src/Actions/ArchiveSubmissionAction.php`
- Create: `packages/forms/src/Actions/MarkSubmissionReadAction.php`
- Create: `packages/forms/src/Actions/MarkSubmissionSpamAction.php`
- Create: `packages/forms/tests/Integration/Actions/SubmissionStatusActionsTest.php`

- [ ] **Step 1: Write status action tests**

Create `packages/forms/tests/Integration/Actions/SubmissionStatusActionsTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Forms\Actions\ArchiveSubmissionAction;
use Capell\Forms\Actions\MarkSubmissionReadAction;
use Capell\Forms\Actions\MarkSubmissionSpamAction;
use Capell\Forms\Enums\SubmissionStatus;
use Capell\Forms\Models\Submission;

it('marks a submission as read', function (): void {
    $submission = Submission::factory()->create(['status' => SubmissionStatus::New]);

    MarkSubmissionReadAction::run($submission);

    expect($submission->refresh()->status)->toBe(SubmissionStatus::Read);
});

it('archives a submission', function (): void {
    $submission = Submission::factory()->create(['status' => SubmissionStatus::Read]);

    ArchiveSubmissionAction::run($submission);

    expect($submission->refresh()->status)->toBe(SubmissionStatus::Archived);
});

it('marks a submission as spam', function (): void {
    $submission = Submission::factory()->create(['status' => SubmissionStatus::New]);

    MarkSubmissionSpamAction::run($submission);

    expect($submission->refresh()->status)->toBe(SubmissionStatus::Spam);
});
```

- [ ] **Step 2: Run status action tests and verify failure**

Run:

```bash
vendor/bin/pest packages/forms/tests/Integration/Actions/SubmissionStatusActionsTest.php
```

Expected: fail because the status actions do not exist.

- [ ] **Step 3: Add status actions**

Create `packages/forms/src/Actions/MarkSubmissionReadAction.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Actions;

use Capell\Forms\Enums\SubmissionStatus;
use Capell\Forms\Models\Submission;
use Lorisleiva\Actions\Concerns\AsAction;

class MarkSubmissionReadAction
{
    use AsAction;

    public function handle(Submission $submission): Submission
    {
        $submission->forceFill(['status' => SubmissionStatus::Read])->save();

        return $submission;
    }
}
```

Create `packages/forms/src/Actions/ArchiveSubmissionAction.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Actions;

use Capell\Forms\Enums\SubmissionStatus;
use Capell\Forms\Models\Submission;
use Lorisleiva\Actions\Concerns\AsAction;

class ArchiveSubmissionAction
{
    use AsAction;

    public function handle(Submission $submission): Submission
    {
        $submission->forceFill(['status' => SubmissionStatus::Archived])->save();

        return $submission;
    }
}
```

Create `packages/forms/src/Actions/MarkSubmissionSpamAction.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Actions;

use Capell\Forms\Enums\SubmissionStatus;
use Capell\Forms\Models\Submission;
use Lorisleiva\Actions\Concerns\AsAction;

class MarkSubmissionSpamAction
{
    use AsAction;

    public function handle(Submission $submission): Submission
    {
        $submission->forceFill(['status' => SubmissionStatus::Spam])->save();

        return $submission;
    }
}
```

- [ ] **Step 4: Run status action tests**

Run:

```bash
vendor/bin/pest packages/forms/tests/Integration/Actions/SubmissionStatusActionsTest.php
```

Expected: pass.

- [ ] **Step 5: Commit**

```bash
git add packages/forms/src/Actions/ArchiveSubmissionAction.php packages/forms/src/Actions/MarkSubmissionReadAction.php packages/forms/src/Actions/MarkSubmissionSpamAction.php packages/forms/tests/Integration/Actions/SubmissionStatusActionsTest.php
git commit -m "feat: add submission status actions"
```

---

## Task 5: Package Provider, Resources, And Translations

**Files:**

- Modify: `packages/forms/src/Providers/FormsServiceProvider.php`
- Create: `packages/forms/config/capell-forms.php`
- Create: `packages/forms/src/Enums/ResourceEnum.php`
- Create: `packages/forms/src/Enums/LivewireComponentEnum.php`
- Create: `packages/forms/resources/lang/en/form.php`
- Create: `packages/forms/resources/lang/en/generic.php`
- Create: `packages/forms/resources/lang/en/messages.php`
- Create: `packages/forms/resources/lang/en/navigation.php`
- Create: `packages/forms/resources/lang/en/package.php`
- Create: `packages/forms/resources/lang/en/table.php`

- [ ] **Step 1: Add translation files**

Create `packages/forms/resources/lang/en/generic.php`:

```php
<?php

declare(strict_types=1);

return [
    'forms' => 'Forms',
    'form' => 'Form',
    'inbox' => 'Inbox',
    'submission' => 'Submission',
    'submissions' => 'Submissions',
    'submit' => 'Submit',
    'submission_status' => [
        'new' => 'New',
        'read' => 'Read',
        'archived' => 'Archived',
        'spam' => 'Spam',
    ],
];
```

Create `packages/forms/resources/lang/en/form.php`:

```php
<?php

declare(strict_types=1);

return [
    'name' => 'Name',
    'handle' => 'Handle',
    'description' => 'Description',
    'is_active' => 'Active',
    'success_message' => 'Success message',
    'store_submissions' => 'Store submissions',
    'notification_email' => 'Notification email',
    'field_type' => [
        'text' => 'Text',
        'email' => 'Email',
        'textarea' => 'Textarea',
        'select' => 'Select',
        'checkbox' => 'Checkbox',
        'hidden' => 'Hidden',
        'honeypot' => 'Honeypot',
    ],
];
```

Create `packages/forms/resources/lang/en/navigation.php`:

```php
<?php

declare(strict_types=1);

return [
    'forms' => 'Forms',
    'inbox' => 'Inbox',
];
```

Create `packages/forms/resources/lang/en/messages.php`:

```php
<?php

declare(strict_types=1);

return [
    'success' => 'Thank you, your submission has been received.',
];
```

Create `packages/forms/resources/lang/en/package.php`:

```php
<?php

declare(strict_types=1);

return [
    'description' => 'Editor-managed forms and submissions for Capell.',
];
```

Create `packages/forms/resources/lang/en/table.php`:

```php
<?php

declare(strict_types=1);

return [
    'form' => 'Form',
    'status' => 'Status',
    'submitted_at' => 'Submitted',
    'payload' => 'Payload',
    'meta' => 'Meta',
    'ip_address' => 'IP address',
    'user_agent' => 'User agent',
    'url' => 'URL',
    'referer' => 'Referer',
];
```

- [ ] **Step 2: Add config**

Create `packages/forms/config/capell-forms.php`:

```php
<?php

declare(strict_types=1);

return [
    'store_submissions' => true,
    'collect_ip_address' => true,
    'collect_user_agent' => true,
];
```

- [ ] **Step 3: Add resource and Livewire enums**

Create `packages/forms/src/Enums/ResourceEnum.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Enums;

use Capell\Forms\Filament\Resources\Forms\FormResource;
use Capell\Forms\Filament\Resources\Submissions\SubmissionResource;

enum ResourceEnum: string
{
    case Form = FormResource::class;
    case Submission = SubmissionResource::class;
}
```

Create `packages/forms/src/Enums/LivewireComponentEnum.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Enums;

use Capell\Core\Enums\Attribute\Component;
use Capell\Core\Enums\Attribute\EnumAttributeHelper;
use Capell\Core\Enums\Attribute\EnumAttributeInterface;
use Capell\Forms\Livewire\FormComponent;

enum LivewireComponentEnum: string implements EnumAttributeInterface
{
    use EnumAttributeHelper;

    #[Component(FormComponent::class)]
    case Form = 'capell-forms::form';

    public static function getComponents(): array
    {
        $attributes = self::getAllCaseAttributes(Component::class);

        return array_map(fn (?Component $attribute): ?string => $attribute?->class ?? null, $attributes);
    }
}
```

- [ ] **Step 4: Update service provider**

Modify `packages/forms/src/Providers/FormsServiceProvider.php` to follow the Blog/Address package pattern:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Providers;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Actions\RegisterBlazeOptimizedViewsAction;
use Capell\Core\Data\VendorAssetData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Forms\Enums\LivewireComponentEnum;
use Capell\Forms\Enums\ResourceEnum;
use Composer\InstalledVersions;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;

class FormsServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-forms';

    public static string $packageName = 'capell-app/forms';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile()
            ->hasViews(self::$name)
            ->hasTranslations()
            ->hasMigrations([
                'create_forms_table',
                'create_submissions_table',
            ]);
    }

    public function registeringPackage(): void
    {
        $this
            ->registerPackageMetadata()
            ->registerPackageAssets()
            ->registerBlazeComponents();

        $this->booted(function (): void {
            if (! $this->isPackageInstalled()) {
                return;
            }

            $this->bootInstalledPackage();
        });
    }

    private function bootInstalledPackage(): self
    {
        return $this
            ->registerResources()
            ->registerLivewireComponents()
            ->registerBladeComponents();
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::getPackage(static::$packageName)->isInstalled();
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            version: $this->getVersion(),
            description: fn (): string => __('capell-forms::package.description'),
        );

        return $this;
    }

    private function registerPackageAssets(): self
    {
        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindSource('resources/views/**/*.blade.php', static::$packageName),
        );

        return $this;
    }

    private function registerBlazeComponents(): self
    {
        RegisterBlazeOptimizedViewsAction::run(__DIR__ . '/../../resources/views/components');

        return $this;
    }

    private function registerResources(): self
    {
        foreach (ResourceEnum::cases() as $resource) {
            if (! class_exists($resource->value)) {
                continue;
            }

            CapellAdmin::registerResource($resource->name, class: $resource->value);
        }

        return $this;
    }

    private function registerLivewireComponents(): self
    {
        foreach (LivewireComponentEnum::getComponents() as $name => $component) {
            if (! $component || ! class_exists($component)) {
                continue;
            }

            Livewire::component($name, $component);
        }

        return $this;
    }

    private function registerBladeComponents(): self
    {
        Blade::componentNamespace('Capell\\Forms\\View\\Components', 'capell-forms');
        Blade::anonymousComponentNamespace('Capell\\Forms\\View\\Components');

        return $this;
    }

    private function getVersion(): string
    {
        if (! class_exists(InstalledVersions::class)) {
            return 'dev';
        }

        if (! InstalledVersions::isInstalled(static::$packageName)) {
            return 'dev';
        }

        return InstalledVersions::getPrettyVersion(static::$packageName) ?? 'dev';
    }
}
```

- [ ] **Step 5: Run provider-adjacent tests**

Run:

```bash
vendor/bin/pest packages/forms/tests/Arch/FormsIsolationTest.php packages/forms/tests/Unit/Data/FormFieldDataTest.php
```

Expected: pass. The resource enum references classes created in later tasks, so defer full package suite until Task 7.

- [ ] **Step 6: Commit**

```bash
git add packages/forms/config/capell-forms.php packages/forms/src/Providers/FormsServiceProvider.php packages/forms/src/Enums/ResourceEnum.php packages/forms/src/Enums/LivewireComponentEnum.php packages/forms/resources/lang/en/form.php packages/forms/resources/lang/en/generic.php packages/forms/resources/lang/en/messages.php packages/forms/resources/lang/en/navigation.php packages/forms/resources/lang/en/package.php packages/forms/resources/lang/en/table.php
git commit -m "feat: register forms package surfaces"
```

---

## Task 6: Forms Admin Resource

**Files:**

- Create: `packages/forms/src/Filament/Resources/Forms/FormResource.php`
- Create: `packages/forms/src/Filament/Resources/Forms/Pages/ManageForms.php`
- Create: `packages/forms/src/Filament/Resources/Forms/Schemas/FormForm.php`
- Create: `packages/forms/src/Filament/Resources/Forms/Tables/FormsTable.php`
- Create: `packages/forms/tests/Feature/Filament/FormResourceTest.php`

- [ ] **Step 1: Write resource tests**

Create `packages/forms/tests/Feature/Filament/FormResourceTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Forms\Filament\Resources\Forms\Pages\ManageForms;
use Capell\Forms\Models\Form;

use function Pest\Livewire\livewire;

it('lists forms in the admin resource', function (): void {
    $form = Form::factory()->create(['name' => 'Contact']);

    livewire(ManageForms::class)
        ->assertCanSeeTableRecords([$form]);
});

it('creates a form with a simple schema', function (): void {
    livewire(ManageForms::class)
        ->callAction('create', data: [
            'name' => 'Contact',
            'handle' => 'contact',
            'description' => 'Contact form',
            'is_active' => true,
            'schema' => [
                [
                    'key' => 'email',
                    'label' => 'Email',
                    'type' => 'email',
                    'required' => true,
                    'validation_rules' => ['email'],
                ],
            ],
            'settings' => [
                'success_message' => 'Thanks.',
                'store_submissions' => true,
                'notification_email' => null,
                'collect_ip_address' => true,
                'collect_user_agent' => true,
            ],
        ])
        ->assertHasNoActionErrors();

    expect(Form::query()->where('handle', 'contact')->exists())->toBeTrue();
});
```

- [ ] **Step 2: Run resource tests and verify failure**

Run:

```bash
vendor/bin/pest packages/forms/tests/Feature/Filament/FormResourceTest.php
```

Expected: fail because the resource classes do not exist.

- [ ] **Step 3: Add Forms table**

Create `packages/forms/src/Filament/Resources/Forms/Tables/FormsTable.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Filament\Resources\Forms\Tables;

use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Forms\Models\Form;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FormsTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IdentifierColumn::make('id'),
                TextColumn::make('name')
                    ->label(__('capell-forms::form.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('handle')
                    ->label(__('capell-forms::form.handle'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('submissions_count')
                    ->label(__('capell-forms::generic.submissions'))
                    ->counts('submissions')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('capell-forms::form.is_active'))
                    ->boolean(),
                DateColumn::make('created_at'),
                DateColumn::make('updated_at'),
            ])
            ->recordActions([
                EditAction::make('edit'),
                DeleteAction::make('delete'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make('delete'),
            ])
            ->modifyQueryUsing(fn ($query) => $query->withCount('submissions'));
    }
}
```

- [ ] **Step 4: Add Forms schema**

Create `packages/forms/src/Filament/Resources/Forms/Schemas/FormForm.php` with a simple repeater-driven schema:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Filament\Resources\Forms\Schemas;

use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Forms\Enums\FormFieldType;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class FormForm implements FormConfigurator
{
    public static function configure(Schema $configurator, mixed $context = null): Schema
    {
        return $configurator
            ->columns(2)
            ->schema([
                Section::make(__('capell-forms::generic.form'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('capell-forms::form.name'))
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (?string $state, callable $set): mixed => $state ? $set('handle', Str::slug($state)) : null)
                            ->required(),
                        TextInput::make('handle')
                            ->label(__('capell-forms::form.handle'))
                            ->required()
                            ->alphaDash()
                            ->maxLength(120),
                        Textarea::make('description')
                            ->label(__('capell-forms::form.description'))
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label(__('capell-forms::form.is_active'))
                            ->default(true),
                    ]),
                Section::make(__('capell-forms::generic.forms'))
                    ->schema([
                        Repeater::make('schema')
                            ->hiddenLabel()
                            ->reorderable()
                            ->schema([
                                TextInput::make('key')->required()->alphaDash(),
                                TextInput::make('label')->required(),
                                Select::make('type')->options(FormFieldType::class)->required(),
                                Toggle::make('required'),
                                TextInput::make('placeholder'),
                                TextInput::make('help_text'),
                                TagsInput::make('validation_rules'),
                            ])
                            ->columns(2)
                            ->defaultItems(1),
                    ])
                    ->columnSpanFull(),
                Section::make(__('capell-forms::generic.submission'))
                    ->schema([
                        Textarea::make('settings.success_message')
                            ->label(__('capell-forms::form.success_message'))
                            ->default(fn (): string => __('capell-forms::messages.success')),
                        Toggle::make('settings.store_submissions')
                            ->label(__('capell-forms::form.store_submissions'))
                            ->default(true),
                        TextInput::make('settings.notification_email')
                            ->label(__('capell-forms::form.notification_email'))
                            ->email(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
```

- [ ] **Step 5: Add resource and page**

Create `packages/forms/src/Filament/Resources/Forms/FormResource.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Filament\Resources\Forms;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasConfiguredForm;
use Capell\Admin\Filament\Concerns\HasConfiguredTable;
use Capell\Core\Facades\CapellCore;
use Capell\Forms\Filament\Resources\Forms\Pages\ManageForms;
use Capell\Forms\Filament\Resources\Forms\Schemas\FormForm;
use Capell\Forms\Filament\Resources\Forms\Tables\FormsTable;
use Capell\Forms\Models\Form;
use Capell\Forms\Providers\FormsServiceProvider;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;

class FormResource extends Resource
{
    use HasConfiguredForm;
    use HasConfiguredTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string $formConfigurator = FormForm::class;

    protected static string $tableConfigurator = FormsTable::class;

    #[Override]
    public static function form(Schema $configurator): Schema
    {
        return static::getFormConfigurator()::configure($configurator);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return static::getTableConfigurator()::configure($table);
    }

    #[Override]
    public static function getModel(): string
    {
        return Form::class;
    }

    public static function getNavigationLabel(): string
    {
        return (string) __('capell-forms::navigation.forms');
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) __('capell-forms::navigation.forms');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::getPackage(FormsServiceProvider::$packageName)->isInstalled();
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageForms::route('/'),
        ];
    }
}
```

Create `packages/forms/src/Filament/Resources/Forms/Pages/ManageForms.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Filament\Resources\Forms\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Forms\Enums\ResourceEnum;
use Capell\Forms\Filament\Resources\Forms\FormResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Override;

class ManageForms extends ManageRecords
{
    #[Override]
    public static function getResource(): string
    {
        return CapellAdmin::getResource(ResourceEnum::Form);
    }

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
```

- [ ] **Step 6: Run resource tests**

Run:

```bash
vendor/bin/pest packages/forms/tests/Feature/Filament/FormResourceTest.php
```

Expected: pass.

- [ ] **Step 7: Commit**

```bash
git add packages/forms/src/Filament/Resources/Forms packages/forms/tests/Feature/Filament/FormResourceTest.php
git commit -m "feat: add forms admin resource"
```

---

## Task 7: Submission Inbox Resource

**Files:**

- Create: `packages/forms/src/Filament/Resources/Submissions/SubmissionResource.php`
- Create: `packages/forms/src/Filament/Resources/Submissions/Pages/ListSubmissions.php`
- Create: `packages/forms/src/Filament/Resources/Submissions/Pages/ViewSubmission.php`
- Create: `packages/forms/src/Filament/Resources/Submissions/Schemas/SubmissionInfolist.php`
- Create: `packages/forms/src/Filament/Resources/Submissions/Tables/SubmissionsTable.php`
- Create: `packages/forms/tests/Feature/Filament/SubmissionResourceTest.php`

- [ ] **Step 1: Write inbox tests**

Create `packages/forms/tests/Feature/Filament/SubmissionResourceTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Forms\Enums\SubmissionStatus;
use Capell\Forms\Filament\Resources\Submissions\Pages\ListSubmissions;
use Capell\Forms\Models\Submission;

use function Pest\Livewire\livewire;

it('lists submissions in the inbox', function (): void {
    $submission = Submission::factory()->create();

    livewire(ListSubmissions::class)
        ->assertCanSeeTableRecords([$submission]);
});

it('archives submissions from the inbox table', function (): void {
    $submission = Submission::factory()->create(['status' => SubmissionStatus::New]);

    livewire(ListSubmissions::class)
        ->callTableAction('archive', $submission)
        ->assertHasNoTableActionErrors();

    expect($submission->refresh()->status)->toBe(SubmissionStatus::Archived);
});
```

- [ ] **Step 2: Run inbox tests and verify failure**

Run:

```bash
vendor/bin/pest packages/forms/tests/Feature/Filament/SubmissionResourceTest.php
```

Expected: fail because the submission resource classes do not exist.

- [ ] **Step 3: Add submissions table**

Create `packages/forms/src/Filament/Resources/Submissions/Tables/SubmissionsTable.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Filament\Resources\Submissions\Tables;

use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Forms\Actions\ArchiveSubmissionAction;
use Capell\Forms\Actions\MarkSubmissionReadAction;
use Capell\Forms\Actions\MarkSubmissionSpamAction;
use Capell\Forms\Enums\SubmissionStatus;
use Capell\Forms\Models\Submission;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubmissionsTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('form'))
            ->columns([
                IdentifierColumn::make('id'),
                TextColumn::make('form.name')
                    ->label(__('capell-forms::table.form'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('capell-forms::table.status'))
                    ->badge(),
                TextColumn::make('payload.values')
                    ->label(__('capell-forms::table.payload'))
                    ->getStateUsing(fn (Submission $record): string => collect($record->payload->values)->map(fn (mixed $value, string $key): string => "{$key}: {$value}")->join(', '))
                    ->wrap(),
                DateColumn::make('submitted_at'),
            ])
            ->filters([
                SelectFilter::make('form_id')->relationship('form', 'name'),
                SelectFilter::make('status')->options(SubmissionStatus::class),
            ])
            ->recordActions([
                ViewAction::make('view'),
                Action::make('markRead')
                    ->label(__('capell-forms::generic.submission_status.read'))
                    ->action(fn (Submission $record): Submission => MarkSubmissionReadAction::run($record)),
                Action::make('archive')
                    ->label(__('capell-forms::generic.submission_status.archived'))
                    ->action(fn (Submission $record): Submission => ArchiveSubmissionAction::run($record)),
                Action::make('spam')
                    ->label(__('capell-forms::generic.submission_status.spam'))
                    ->color('danger')
                    ->action(fn (Submission $record): Submission => MarkSubmissionSpamAction::run($record)),
            ]);
    }
}
```

- [ ] **Step 4: Add resource, list page, and view page**

Create `packages/forms/src/Filament/Resources/Submissions/SubmissionResource.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Filament\Resources\Submissions;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasConfiguredTable;
use Capell\Core\Facades\CapellCore;
use Capell\Forms\Filament\Resources\Submissions\Pages\ListSubmissions;
use Capell\Forms\Filament\Resources\Submissions\Pages\ViewSubmission;
use Capell\Forms\Filament\Resources\Submissions\Tables\SubmissionsTable;
use Capell\Forms\Models\Submission;
use Capell\Forms\Providers\FormsServiceProvider;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;

class SubmissionResource extends Resource
{
    use HasConfiguredTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?string $recordTitleAttribute = 'id';

    protected static string $tableConfigurator = SubmissionsTable::class;

    #[Override]
    public static function table(Table $table): Table
    {
        return static::getTableConfigurator()::configure($table);
    }

    #[Override]
    public static function getModel(): string
    {
        return Submission::class;
    }

    public static function getNavigationLabel(): string
    {
        return (string) __('capell-forms::navigation.inbox');
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) __('capell-forms::navigation.forms');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::getPackage(FormsServiceProvider::$packageName)->isInstalled();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubmissions::route('/'),
            'view' => ViewSubmission::route('/{record}'),
        ];
    }
}
```

Create `packages/forms/src/Filament/Resources/Submissions/Pages/ListSubmissions.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Filament\Resources\Submissions\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Forms\Enums\ResourceEnum;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListSubmissions extends ListRecords
{
    #[Override]
    public static function getResource(): string
    {
        return CapellAdmin::getResource(ResourceEnum::Submission);
    }
}
```

Create `packages/forms/src/Filament/Resources/Submissions/Schemas/SubmissionInfolist.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Filament\Resources\Submissions\Schemas;

use Capell\Forms\Models\Submission;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubmissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Section::make(__('capell-forms::generic.submission'))
                    ->schema([
                        TextEntry::make('form.name')->label(__('capell-forms::table.form')),
                        TextEntry::make('status')->badge()->label(__('capell-forms::table.status')),
                        TextEntry::make('submitted_at')->dateTime()->label(__('capell-forms::table.submitted_at')),
                    ]),
                Section::make(__('capell-forms::table.payload'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('payload.values')
                            ->hiddenLabel()
                            ->getStateUsing(fn (Submission $record): string => collect($record->payload->values)
                                ->map(fn (mixed $value, string $key): string => "{$key}: {$value}")
                                ->join(PHP_EOL))
                            ->prose(),
                    ]),
                Section::make(__('capell-forms::table.meta'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('meta.ipAddress')->label(__('capell-forms::table.ip_address')),
                        TextEntry::make('meta.userAgent')->label(__('capell-forms::table.user_agent')),
                        TextEntry::make('meta.url')->label(__('capell-forms::table.url')),
                        TextEntry::make('meta.referer')->label(__('capell-forms::table.referer')),
                    ]),
            ]);
    }
}
```

Create `packages/forms/src/Filament/Resources/Submissions/Pages/ViewSubmission.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Filament\Resources\Submissions\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Forms\Enums\ResourceEnum;
use Capell\Forms\Filament\Resources\Submissions\Schemas\SubmissionInfolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Override;

class ViewSubmission extends ViewRecord
{
    #[Override]
    public static function getResource(): string
    {
        return CapellAdmin::getResource(ResourceEnum::Submission);
    }

    #[Override]
    public function infolist(Schema $schema): Schema
    {
        return SubmissionInfolist::configure($schema);
    }
}
```

- [ ] **Step 5: Run inbox tests**

Run:

```bash
vendor/bin/pest packages/forms/tests/Feature/Filament/SubmissionResourceTest.php
```

Expected: pass.

- [ ] **Step 6: Commit**

```bash
git add packages/forms/src/Filament/Resources/Submissions packages/forms/tests/Feature/Filament/SubmissionResourceTest.php
git commit -m "feat: add forms inbox resource"
```

---

## Task 8: Frontend Blade And Livewire Form

**Files:**

- Create: `packages/forms/src/View/Components/Form.php`
- Create: `packages/forms/src/Livewire/FormComponent.php`
- Create: `packages/forms/resources/views/components/form.blade.php`
- Create: `packages/forms/resources/views/livewire/form.blade.php`
- Create: `packages/forms/resources/views/fields/checkbox.blade.php`
- Create: `packages/forms/resources/views/fields/email.blade.php`
- Create: `packages/forms/resources/views/fields/honeypot.blade.php`
- Create: `packages/forms/resources/views/fields/hidden.blade.php`
- Create: `packages/forms/resources/views/fields/select.blade.php`
- Create: `packages/forms/resources/views/fields/text.blade.php`
- Create: `packages/forms/resources/views/fields/textarea.blade.php`
- Create: `packages/forms/tests/Feature/Livewire/FormComponentTest.php`

- [ ] **Step 1: Write frontend tests**

Create `packages/forms/tests/Feature/Livewire/FormComponentTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Forms\Livewire\FormComponent;
use Capell\Forms\Models\Form;

use function Pest\Livewire\livewire;

it('renders an active form by handle', function (): void {
    Form::factory()->create([
        'handle' => 'contact',
        'name' => 'Contact',
        'is_active' => true,
        'schema' => [
            ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'validation_rules' => ['email']],
        ],
    ]);

    livewire(FormComponent::class, ['handle' => 'contact'])
        ->assertSee('Email');
});

it('stores a valid submission and shows the success message', function (): void {
    Form::factory()->create([
        'handle' => 'contact',
        'settings' => ['success_message' => 'Message received.', 'store_submissions' => true],
        'schema' => [
            ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'validation_rules' => ['email']],
        ],
    ]);

    livewire(FormComponent::class, ['handle' => 'contact'])
        ->set('state.email', 'ben@example.com')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSee('Message received.');
});
```

- [ ] **Step 2: Run frontend tests and verify failure**

Run:

```bash
vendor/bin/pest packages/forms/tests/Feature/Livewire/FormComponentTest.php
```

Expected: fail because the component and views do not exist.

- [ ] **Step 3: Add Blade wrapper component**

Create `packages/forms/src/View/Components/Form.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Form extends Component
{
    public function __construct(
        public string $handle,
    ) {}

    public function render(): View|Closure|string
    {
        return view('capell-forms::components.form');
    }
}
```

Create `packages/forms/resources/views/components/form.blade.php`:

```blade
<livewire:capell-forms::form :handle="$handle" />
```

- [ ] **Step 4: Add Livewire component**

Create `packages/forms/src/Livewire/FormComponent.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Forms\Livewire;

use Capell\Forms\Actions\CreateSubmissionAction;
use Capell\Forms\Data\SubmissionMetaData;
use Capell\Forms\Models\Form;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class FormComponent extends Component
{
    public string $handle;

    /** @var array<string, mixed> */
    public array $state = [];

    public bool $submitted = false;

    public function mount(string $handle): void
    {
        $this->handle = $handle;
    }

    public function submit(): void
    {
        $form = $this->resolveForm();

        CreateSubmissionAction::run(
            form: $form,
            input: $this->state,
            meta: new SubmissionMetaData(
                ipAddress: request()->ip(),
                userAgent: request()->userAgent(),
                url: request()->headers->get('referer'),
                referer: request()->headers->get('referer'),
            ),
        );

        $this->submitted = true;
        $this->state = [];
    }

    public function render(): View
    {
        return view('capell-forms::livewire.form', [
            'form' => $this->resolveForm(),
        ]);
    }

    private function resolveForm(): Form
    {
        return Form::query()
            ->active()
            ->where('handle', $this->handle)
            ->firstOrFail();
    }
}
```

- [ ] **Step 5: Add Livewire view and field partials**

Create `packages/forms/resources/views/livewire/form.blade.php`:

```blade
<form wire:submit="submit">
    @if ($submitted)
        <p>{{ $form->settings->successMessage ?? __('capell-forms::messages.success') }}</p>
    @else
        @foreach ($form->schema as $field)
            @includeIf("capell-forms::fields.{$field->type->value}", ['field' => $field])
        @endforeach

        <button type="submit">
            {{ __('capell-forms::generic.submit') }}
        </button>
    @endif
</form>
```

Create `packages/forms/resources/views/fields/email.blade.php`:

```blade
<label>
    <span>{{ $field->label }}</span>
    <input type="email" wire:model.defer="state.{{ $field->key }}" placeholder="{{ $field->placeholder }}" />
</label>
@error($field->key)
    <p>{{ $message }}</p>
@enderror
```

Create `packages/forms/resources/views/fields/text.blade.php`:

```blade
<label>
    <span>{{ $field->label }}</span>
    <input type="text" wire:model.defer="state.{{ $field->key }}" placeholder="{{ $field->placeholder }}" />
</label>
@error($field->key)
    <p>{{ $message }}</p>
@enderror
```

Create `packages/forms/resources/views/fields/textarea.blade.php`:

```blade
<label>
    <span>{{ $field->label }}</span>
    <textarea wire:model.defer="state.{{ $field->key }}" placeholder="{{ $field->placeholder }}"></textarea>
</label>
@error($field->key)
    <p>{{ $message }}</p>
@enderror
```

Create `packages/forms/resources/views/fields/select.blade.php`:

```blade
<label>
    <span>{{ $field->label }}</span>
    <select wire:model.defer="state.{{ $field->key }}">
        <option value=""></option>
        @foreach ($field->options as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
</label>
@error($field->key)
    <p>{{ $message }}</p>
@enderror
```

Create `packages/forms/resources/views/fields/checkbox.blade.php`:

```blade
<label>
    <input type="checkbox" wire:model.defer="state.{{ $field->key }}" />
    <span>{{ $field->label }}</span>
</label>
@error($field->key)
    <p>{{ $message }}</p>
@enderror
```

Create `packages/forms/resources/views/fields/hidden.blade.php`:

```blade
<input type="hidden" wire:model.defer="state.{{ $field->key }}" />
```

Create `packages/forms/resources/views/fields/honeypot.blade.php`:

```blade
<div class="hidden" aria-hidden="true">
    <label>
        <span>{{ $field->label }}</span>
        <input type="text" tabindex="-1" autocomplete="off" wire:model.defer="state.{{ $field->key }}" />
    </label>
</div>
```

- [ ] **Step 6: Run frontend tests**

Run:

```bash
vendor/bin/pest packages/forms/tests/Feature/Livewire/FormComponentTest.php
```

Expected: pass.

- [ ] **Step 7: Commit**

```bash
git add packages/forms/src/View/Components/Form.php packages/forms/src/Livewire/FormComponent.php packages/forms/resources/views/components/form.blade.php packages/forms/resources/views/livewire/form.blade.php packages/forms/resources/views/fields packages/forms/resources/lang/en/generic.php packages/forms/tests/Feature/Livewire/FormComponentTest.php
git commit -m "feat: add frontend form component"
```

---

## Task 9: README, Package Tests, And Preflight

**Files:**

- Modify: `packages/forms/README.md`

- [ ] **Step 1: Update README**

Replace `packages/forms/README.md` with concise package documentation:

```markdown
# Capell Forms

Forms adds editor-managed forms and a submission inbox to Capell.

## Install

```bash
composer require capell-app/forms
```

Run the host application's normal migration/deployment flow after installation.

## Render a form

```blade
<x-capell-forms::form handle="contact" />
```

## Admin

- Forms: create and manage simple form definitions.
- Inbox: review submitted form payloads and mark submissions as read, archived, or spam.

## Extension points

- Override package views to change frontend markup.
- Listen for `Capell\Forms\Events\FormSubmitted` to send email, Slack messages, CRM syncs, or webhooks.
- Keep business behavior in project listeners or Actions rather than editing package views.
```

- [ ] **Step 2: Run the package suite**

Run:

```bash
vendor/bin/pest packages/forms/tests
```

Expected: pass.

- [ ] **Step 3: Run lint and analysis for touched package**

Run:

```bash
composer lint
composer analyze
```

Expected: pass.

- [ ] **Step 4: Run full preflight before final handoff**

Run:

```bash
composer preflight
```

Expected: pass.

- [ ] **Step 5: Commit**

```bash
git add packages/forms/README.md
git commit -m "docs: document forms package"
```

---

## Task 10: Newsletter Signup Package

**Files:**

- Create: `packages/newsletter/composer.json`
- Create: `packages/newsletter/capell.json`
- Create: `packages/newsletter/resources/lang/en/generic.php`
- Create: `packages/newsletter/resources/lang/en/package.php`
- Create: `packages/newsletter/resources/views/components/widget/newsletter-signup.blade.php`
- Create: `packages/newsletter/src/Actions/CreateNewsletterSignupFormAction.php`
- Create: `packages/newsletter/src/Actions/CreateNewsletterSignupWidgetAction.php`
- Create: `packages/newsletter/src/Events/NewsletterSignupSubmitted.php`
- Create: `packages/newsletter/src/Filament/Configurators/Widgets/NewsletterSignupWidgetConfigurator.php`
- Create: `packages/newsletter/src/Listeners/DispatchNewsletterSignupSubmittedListener.php`
- Create: `packages/newsletter/src/Providers/NewsletterServiceProvider.php`
- Create: `packages/newsletter/tests/NewsletterTestCase.php`
- Create: `packages/newsletter/tests/Integration/CreateNewsletterSignupWidgetActionTest.php`
- Create: `packages/newsletter/tests/Unit/ManifestRequirementsTest.php`

- [ ] **Step 1: Create package manifests**

Create `packages/newsletter/composer.json`:

```json
{
    "name": "capell-app/newsletter",
    "description": "Newsletter signup widgets for Capell",
    "keywords": [
        "capell",
        "newsletter",
        "forms",
        "mosaic",
        "laravel",
        "cms"
    ],
    "license": "proprietary",
    "require": {
        "php": "^8.2",
        "capell-app/forms": "*",
        "capell-app/mosaic": "*"
    },
    "autoload": {
        "psr-4": {
            "Capell\\Newsletter\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Capell\\Newsletter\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Capell\\Newsletter\\Providers\\NewsletterServiceProvider"
            ]
        }
    },
    "config": {
        "sort-packages": true
    },
    "prefer-stable": true
}
```

Create `packages/newsletter/capell.json`:

```json
{
    "name": "capell-app/newsletter",
    "kind": "package",
    "capell-version": "^4.0",
    "contexts": ["admin", "frontend"],
    "requires": ["capell-app/forms", "capell-app/mosaic"],
    "providers": {
        "shared": ["Capell\\Newsletter\\Providers\\NewsletterServiceProvider"]
    }
}
```

- [ ] **Step 2: Write manifest tests**

Create `packages/newsletter/tests/Unit/ManifestRequirementsTest.php`:

```php
<?php

declare(strict_types=1);

it('declares forms and mosaic as package requirements', function (): void {
    $manifest = json_decode(
        file_get_contents(__DIR__ . '/../../capell.json'),
        associative: true,
    );

    expect($manifest['requires'])->toContain('capell-app/forms')
        ->and($manifest['requires'])->toContain('capell-app/mosaic');
});
```

- [ ] **Step 3: Add package translations**

Create `packages/newsletter/resources/lang/en/generic.php`:

```php
<?php

declare(strict_types=1);

return [
    'newsletter' => 'Newsletter',
    'newsletter_signup' => 'Newsletter signup',
    'email' => 'Email address',
    'intro' => 'Intro',
    'submit' => 'Subscribe',
    'success' => 'You are signed up.',
];
```

Create `packages/newsletter/resources/lang/en/package.php`:

```php
<?php

declare(strict_types=1);

return [
    'description' => 'Newsletter signup widgets powered by Forms and Mosaic.',
];
```

- [ ] **Step 4: Add service provider**

Create `packages/newsletter/src/Providers/NewsletterServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Newsletter\Providers;

use Capell\Core\Data\VendorAssetData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Forms\Events\FormSubmitted;
use Capell\Newsletter\Listeners\DispatchNewsletterSignupSubmittedListener;
use Composer\InstalledVersions;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;

class NewsletterServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-newsletter';

    public static string $packageName = 'capell-app/newsletter';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasViews(self::$name)
            ->hasTranslations();
    }

    public function registeringPackage(): void
    {
        $this
            ->registerPackageMetadata()
            ->registerPackageAssets()
            ->registerListeners();
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            version: $this->getVersion(),
            description: fn (): string => __('capell-newsletter::package.description'),
        );

        return $this;
    }

    private function registerPackageAssets(): self
    {
        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindSource('resources/views/**/*.blade.php', static::$packageName),
        );

        return $this;
    }

    private function registerListeners(): self
    {
        Event::listen(FormSubmitted::class, DispatchNewsletterSignupSubmittedListener::class);

        return $this;
    }

    private function getVersion(): string
    {
        if (! class_exists(InstalledVersions::class)) {
            return 'dev';
        }

        if (! InstalledVersions::isInstalled(static::$packageName)) {
            return 'dev';
        }

        return InstalledVersions::getPrettyVersion(static::$packageName) ?? 'dev';
    }
}
```

- [ ] **Step 5: Create default newsletter form action**

Create `packages/newsletter/src/Actions/CreateNewsletterSignupFormAction.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Newsletter\Actions;

use Capell\Forms\Models\Form;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateNewsletterSignupFormAction
{
    use AsAction;

    public function handle(?int $siteId = null): Form
    {
        return Form::query()->updateOrCreate([
            'site_id' => $siteId,
            'handle' => 'newsletter-signup',
        ], [
            'name' => __('capell-newsletter::generic.newsletter_signup'),
            'description' => null,
            'schema' => [
                [
                    'key' => 'email',
                    'label' => __('capell-newsletter::generic.email'),
                    'type' => 'email',
                    'required' => true,
                    'validation_rules' => ['email'],
                ],
            ],
            'settings' => [
                'success_message' => __('capell-newsletter::generic.success'),
                'store_submissions' => true,
            ],
            'is_active' => true,
        ]);
    }
}
```

- [ ] **Step 6: Create newsletter Mosaic widget action**

Create `packages/newsletter/src/Actions/CreateNewsletterSignupWidgetAction.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Newsletter\Actions;

use Capell\Core\Models\Type;
use Capell\Mosaic\Enums\LayoutTypeEnum;
use Capell\Mosaic\Enums\WidgetTypeEnum;
use Capell\Mosaic\Filament\Configurators\Types\WidgetTypeConfigurator;
use Capell\Mosaic\Models\Widget;
use Capell\Newsletter\Filament\Configurators\Widgets\NewsletterSignupWidgetConfigurator;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateNewsletterSignupWidgetAction
{
    use AsAction;

    public function handle(): Widget
    {
        return Widget::query()->updateOrCreate([
            'key' => 'newsletter-signup',
        ], [
            'name' => __('capell-newsletter::generic.newsletter_signup'),
            'type_id' => $this->widgetType()->id,
            'meta' => [
                'component' => 'capell-newsletter::widget.newsletter-signup',
                'form_handle' => 'newsletter-signup',
            ],
            'admin' => [
                'icon' => 'heroicon-o-envelope',
                'configurator' => NewsletterSignupWidgetConfigurator::getKey(),
            ],
        ]);
    }

    private function widgetType(): Type
    {
        return Type::query()->firstOrCreate([
            'key' => WidgetTypeEnum::Default,
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-newsletter::generic.newsletter'),
            'admin' => [
                'type_configurator' => WidgetTypeConfigurator::getKey(),
                'configurator' => NewsletterSignupWidgetConfigurator::getKey(),
                'icon' => 'heroicon-o-envelope',
            ],
        ]);
    }
}
```

- [ ] **Step 7: Add widget configurator**

Create `packages/newsletter/src/Filament/Configurators/Widgets/NewsletterSignupWidgetConfigurator.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Newsletter\Filament\Configurators\Widgets;

use Capell\Mosaic\Filament\Configurators\Widgets\DefaultWidgetConfigurator;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs\Tab;

class NewsletterSignupWidgetConfigurator extends DefaultWidgetConfigurator
{
    protected function detailsTab(): Tab
    {
        return Tab::make('details')
            ->label(__('capell-admin::tab.details'))
            ->icon('heroicon-o-information-circle')
            ->statePath('meta')
            ->schema([
                TextInput::make('heading')
                    ->label(__('capell-newsletter::generic.newsletter_signup')),
                Textarea::make('intro')
                    ->label(__('capell-newsletter::generic.intro')),
                TextInput::make('form_handle')
                    ->label(__('capell-forms::form.handle'))
                    ->default('newsletter-signup')
                    ->required(),
                TextInput::make('submit_label')
                    ->label(__('capell-newsletter::generic.submit'))
                    ->default(fn (): string => __('capell-newsletter::generic.submit')),
            ]);
    }
}
```

The configurator stores values in widget meta and does not create submissions itself.

- [ ] **Step 8: Add widget view**

Create `packages/newsletter/resources/views/components/widget/newsletter-signup.blade.php`:

```blade
@props([
    'widget',
    'widgetData' => [],
])

@php
    $formHandle = $widgetData['form_handle'] ?? ($widget->meta['form_handle'] ?? 'newsletter-signup');
@endphp

<section {{ $attributes }}>
    @if (! empty($widgetData['heading']))
        <h2>{{ $widgetData['heading'] }}</h2>
    @endif

    @if (! empty($widgetData['intro']))
        <p>{{ $widgetData['intro'] }}</p>
    @endif

    <x-capell-forms::form :handle="$formHandle" />
</section>
```

- [ ] **Step 9: Add newsletter event**

Create `packages/newsletter/src/Events/NewsletterSignupSubmitted.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Newsletter\Events;

use Capell\Forms\Models\Submission;
use Illuminate\Foundation\Events\Dispatchable;

class NewsletterSignupSubmitted
{
    use Dispatchable;

    public function __construct(
        public Submission $submission,
    ) {}
}
```

Create `packages/newsletter/src/Listeners/DispatchNewsletterSignupSubmittedListener.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Newsletter\Listeners;

use Capell\Forms\Events\FormSubmitted;
use Capell\Newsletter\Events\NewsletterSignupSubmitted;

class DispatchNewsletterSignupSubmittedListener
{
    public function handle(FormSubmitted $event): void
    {
        if ($event->form->handle !== 'newsletter-signup') {
            return;
        }

        NewsletterSignupSubmitted::dispatch($event->submission);
    }
}
```

Do not add email marketing provider integrations.

- [ ] **Step 10: Add action tests**

Create `packages/newsletter/tests/NewsletterTestCase.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Newsletter\Tests;

use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Forms\Providers\FormsServiceProvider;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Mosaic\Providers\MosaicServiceProvider;
use Capell\Newsletter\Providers\NewsletterServiceProvider;
use Capell\Tests\AbstractTestCase;
use Livewire\LivewireServiceProvider;
use Override;

class NewsletterTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-newsletter';
    }

    /**
     * @return class-string[]
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AdminServiceProvider::class,
            FrontendServiceProvider::class,
            FormsServiceProvider::class,
            MosaicServiceProvider::class,
            NewsletterServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::registerPackage(FormsServiceProvider::$packageName, path: realpath(__DIR__ . '/../../forms'));
        CapellCore::registerPackage(MosaicServiceProvider::$packageName, path: realpath(__DIR__ . '/../../mosaic'));
        CapellCore::registerPackage(NewsletterServiceProvider::$packageName, path: realpath(__DIR__ . '/..'));

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(FrontendServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(FormsServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(MosaicServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(NewsletterServiceProvider::$packageName);
    }
}
```

Create `packages/newsletter/tests/Integration/CreateNewsletterSignupWidgetActionTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Forms\Models\Form;
use Capell\Mosaic\Models\Widget;
use Capell\Newsletter\Actions\CreateNewsletterSignupFormAction;
use Capell\Newsletter\Actions\CreateNewsletterSignupWidgetAction;

it('creates the default newsletter signup form', function (): void {
    $form = CreateNewsletterSignupFormAction::run();

    expect($form)->toBeInstanceOf(Form::class)
        ->and($form->handle)->toBe('newsletter-signup')
        ->and($form->schema->first()->key)->toBe('email');
});

it('creates the newsletter signup mosaic widget', function (): void {
    $widget = CreateNewsletterSignupWidgetAction::run();

    expect($widget)->toBeInstanceOf(Widget::class)
        ->and($widget->key)->toBe('newsletter-signup')
        ->and($widget->meta['component'])->toBe('capell-newsletter::widget.newsletter-signup')
        ->and($widget->meta['form_handle'])->toBe('newsletter-signup');
});
```

Run:

```bash
vendor/bin/pest packages/newsletter/tests
```

Expected: pass.

- [ ] **Step 11: Commit**

```bash
git add packages/newsletter
git commit -m "feat: add newsletter signup package"
```

---

## Self-Review Checklist

- The plan implements every spec item: editor-managed forms, compact field types, frontend rendering, validation, stored submissions, inbox workflows, events, translations, docs, and tests.
- The Newsletter package is separate from Forms and depends on both Forms and Mosaic for the signup widget.
- The plan keeps excluded v1 features out: uploads, payments, multi-step forms, analytics, conditional logic, and CRM/webhook delivery.
- The plan uses Capell conventions: strict types, Actions, Data objects, enums for persisted values, package translations, Filament labels via methods/configurators, and direct package test commands.
- The plan does not modify unrelated packages.
- The final implementation must run `vendor/bin/pest packages/forms/tests` and `composer preflight` before merge or PR.
