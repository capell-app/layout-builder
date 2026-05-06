# FormBuilder Package Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the existing `capell-app/form-builder` stub into a small Capell package for editor-managed form-builder, Livewire-backed frontend submission, stored submissions, and an admin submission inbox, then add a separate Newsletter package that depends on FormBuilder and LayoutBuilder for the newsletter signup widget.

**Architecture:** Keep all FormBuilder behavior in Actions, all cross-layer structures in Spatie Data objects, all persisted option values in enums, and all admin UI in the established Filament Resource/Schemas/Tables/Pages split. The FormBuilder frontend surface is a Blade component that mounts one package Livewire component; projects customise markup by overriding views and customise side effects by listening to `FormSubmitted`. LayoutBuilder-specific form experiences live in dependent packages, starting with `capell-app/newsletter`, so FormBuilder does not depend on LayoutBuilder.

**Tech Stack:** PHP 8.2, Laravel, Capell Core/Admin/Frontend, Filament, Livewire, Spatie Laravel Data, Lorisleiva Actions, Pest.

---

## File Structure

### Modify

- `packages/form-builder/composer.json` - move factory autoloading into package `autoload`, keep package dependencies lean.
- `packages/form-builder/database/migrations/create_form-builder_table.php` - add handle, schema, settings, active state, site scoped uniqueness.
- `packages/form-builder/database/migrations/create_submissions_table.php` - add site, payload, meta, status, submitted timestamp indexes.
- `packages/form-builder/src/Models/Form.php` - casts, relationships, scopes, factory, fillable.
- `packages/form-builder/src/Models/Submission.php` - casts, relationships, status scopes, factory, fillable.
- `packages/form-builder/src/Providers/FormBuilderServiceProvider.php` - package metadata, translations, views, resources, Livewire, Blade components.
- `packages/form-builder/tests/FormBuilderTestCase.php` - load migrations and Livewire package support needed by the new tests.
- `packages/form-builder/README.md` - document install, usage, admin areas, extension points.

### Create

- `packages/form-builder/config/capell-form-builder.php`
- `packages/form-builder/database/factories/FormFactory.php`
- `packages/form-builder/database/factories/SubmissionFactory.php`
- `packages/form-builder/resources/lang/en/form.php`
- `packages/form-builder/resources/lang/en/generic.php`
- `packages/form-builder/resources/lang/en/messages.php`
- `packages/form-builder/resources/lang/en/navigation.php`
- `packages/form-builder/resources/lang/en/package.php`
- `packages/form-builder/resources/lang/en/table.php`
- `packages/form-builder/resources/views/components/form.blade.php`
- `packages/form-builder/resources/views/livewire/form.blade.php`
- `packages/form-builder/resources/views/fields/checkbox.blade.php`
- `packages/form-builder/resources/views/fields/email.blade.php`
- `packages/form-builder/resources/views/fields/honeypot.blade.php`
- `packages/form-builder/resources/views/fields/hidden.blade.php`
- `packages/form-builder/resources/views/fields/select.blade.php`
- `packages/form-builder/resources/views/fields/text.blade.php`
- `packages/form-builder/resources/views/fields/textarea.blade.php`
- `packages/form-builder/src/Actions/ArchiveSubmissionAction.php`
- `packages/form-builder/src/Actions/BuildFormValidationRulesAction.php`
- `packages/form-builder/src/Actions/CreateSubmissionAction.php`
- `packages/form-builder/src/Actions/MarkSubmissionReadAction.php`
- `packages/form-builder/src/Actions/MarkSubmissionSpamAction.php`
- `packages/form-builder/src/Data/FormFieldData.php`
- `packages/form-builder/src/Data/FormSettingsData.php`
- `packages/form-builder/src/Data/SubmissionMetaData.php`
- `packages/form-builder/src/Data/SubmissionPayloadData.php`
- `packages/form-builder/src/Enums/FormFieldType.php`
- `packages/form-builder/src/Enums/LivewireComponentEnum.php`
- `packages/form-builder/src/Enums/ResourceEnum.php`
- `packages/form-builder/src/Enums/SubmissionStatus.php`
- `packages/form-builder/src/Events/FormSubmitted.php`
- `packages/form-builder/src/Filament/Resources/FormBuilder/FormResource.php`
- `packages/form-builder/src/Filament/Resources/FormBuilder/Pages/ManageFormBuilder.php`
- `packages/form-builder/src/Filament/Resources/FormBuilder/Schemas/FormForm.php`
- `packages/form-builder/src/Filament/Resources/FormBuilder/Tables/FormBuilderTable.php`
- `packages/form-builder/src/Filament/Resources/Submissions/Pages/ListSubmissions.php`
- `packages/form-builder/src/Filament/Resources/Submissions/Pages/ViewSubmission.php`
- `packages/form-builder/src/Filament/Resources/Submissions/SubmissionResource.php`
- `packages/form-builder/src/Filament/Resources/Submissions/Schemas/SubmissionInfolist.php`
- `packages/form-builder/src/Filament/Resources/Submissions/Tables/SubmissionsTable.php`
- `packages/form-builder/src/Livewire/FormComponent.php`
- `packages/form-builder/src/View/Components/Form.php`
- `packages/form-builder/tests/Feature/Filament/FormResourceTest.php`
- `packages/form-builder/tests/Feature/Filament/SubmissionResourceTest.php`
- `packages/form-builder/tests/Feature/Livewire/FormComponentTest.php`
- `packages/form-builder/tests/Integration/Actions/CreateSubmissionActionTest.php`
- `packages/form-builder/tests/Integration/Actions/SubmissionStatusActionsTest.php`
- `packages/form-builder/tests/Integration/Models/FormModelTest.php`
- `packages/form-builder/tests/Integration/Models/SubmissionModelTest.php`
- `packages/form-builder/tests/Unit/Actions/BuildFormValidationRulesActionTest.php`
- `packages/form-builder/tests/Unit/Data/FormFieldDataTest.php`
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

- Create: `packages/form-builder/src/Enums/FormFieldType.php`
- Create: `packages/form-builder/src/Enums/SubmissionStatus.php`
- Create: `packages/form-builder/src/Data/FormFieldData.php`
- Create: `packages/form-builder/src/Data/FormSettingsData.php`
- Create: `packages/form-builder/src/Data/SubmissionPayloadData.php`
- Create: `packages/form-builder/src/Data/SubmissionMetaData.php`
- Create: `packages/form-builder/tests/Unit/Data/FormFieldDataTest.php`

- [ ] **Step 1: Write enum and data tests**

Create `packages/form-builder/tests/Unit/Data/FormFieldDataTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\FormBuilder\Data\FormFieldData;
use Capell\FormBuilder\Data\FormSettingsData;
use Capell\FormBuilder\Data\SubmissionPayloadData;
use Capell\FormBuilder\Enums\FormFieldType;

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
vendor/bin/pest packages/form-builder/tests/Unit/Data/FormFieldDataTest.php
```

Expected: fail because the data and enum classes do not exist.

- [ ] **Step 3: Add field type enum**

Create `packages/form-builder/src/Enums/FormFieldType.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Enums;

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
            self::Text => __('capell-form-builder::form.field_type.text'),
            self::Email => __('capell-form-builder::form.field_type.email'),
            self::Textarea => __('capell-form-builder::form.field_type.textarea'),
            self::Select => __('capell-form-builder::form.field_type.select'),
            self::Checkbox => __('capell-form-builder::form.field_type.checkbox'),
            self::Hidden => __('capell-form-builder::form.field_type.hidden'),
            self::Honeypot => __('capell-form-builder::form.field_type.honeypot'),
        };
    }

    public function isStoredInPayload(): bool
    {
        return $this !== self::Honeypot;
    }
}
```

- [ ] **Step 4: Add submission status enum**

Create `packages/form-builder/src/Enums/SubmissionStatus.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Enums;

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
            self::New => __('capell-form-builder::generic.submission_status.new'),
            self::Read => __('capell-form-builder::generic.submission_status.read'),
            self::Archived => __('capell-form-builder::generic.submission_status.archived'),
            self::Spam => __('capell-form-builder::generic.submission_status.spam'),
        };
    }
}
```

- [ ] **Step 5: Add data objects**

Create `packages/form-builder/src/Data/FormFieldData.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Data;

use Capell\FormBuilder\Enums\FormFieldType;
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

Create `packages/form-builder/src/Data/FormSettingsData.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Data;

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

Create `packages/form-builder/src/Data/SubmissionPayloadData.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Data;

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

Create `packages/form-builder/src/Data/SubmissionMetaData.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Data;

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
vendor/bin/pest packages/form-builder/tests/Unit/Data/FormFieldDataTest.php
```

Expected: pass.

- [ ] **Step 7: Commit**

```bash
git add packages/form-builder/src/Enums/FormFieldType.php packages/form-builder/src/Enums/SubmissionStatus.php packages/form-builder/src/Data/FormFieldData.php packages/form-builder/src/Data/FormSettingsData.php packages/form-builder/src/Data/SubmissionPayloadData.php packages/form-builder/src/Data/SubmissionMetaData.php packages/form-builder/tests/Unit/Data/FormFieldDataTest.php
git commit -m "feat: add form-builder data objects"
```

---

## Task 2: Database, Models, And Factories

**Files:**

- Modify: `packages/form-builder/composer.json`
- Modify: `packages/form-builder/database/migrations/create_form-builder_table.php`
- Modify: `packages/form-builder/database/migrations/create_submissions_table.php`
- Modify: `packages/form-builder/src/Models/Form.php`
- Modify: `packages/form-builder/src/Models/Submission.php`
- Create: `packages/form-builder/database/factories/FormFactory.php`
- Create: `packages/form-builder/database/factories/SubmissionFactory.php`
- Create: `packages/form-builder/tests/Integration/Models/FormModelTest.php`
- Create: `packages/form-builder/tests/Integration/Models/SubmissionModelTest.php`

- [ ] **Step 1: Write model tests**

Create `packages/form-builder/tests/Integration/Models/FormModelTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\FormBuilder\Data\FormFieldData;
use Capell\FormBuilder\Data\FormSettingsData;
use Capell\FormBuilder\Enums\FormFieldType;
use Capell\FormBuilder\Models\Form;
use Capell\FormBuilder\Models\Submission;

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

it('scopes active form-builder', function (): void {
    Form::factory()->create(['handle' => 'enabled', 'is_active' => true]);
    Form::factory()->create(['handle' => 'disabled', 'is_active' => false]);

    expect(Form::query()->active()->pluck('handle')->all())->toBe(['enabled']);
});
```

Create `packages/form-builder/tests/Integration/Models/SubmissionModelTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\FormBuilder\Data\SubmissionMetaData;
use Capell\FormBuilder\Data\SubmissionPayloadData;
use Capell\FormBuilder\Enums\SubmissionStatus;
use Capell\FormBuilder\Models\Form;
use Capell\FormBuilder\Models\Submission;

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
vendor/bin/pest packages/form-builder/tests/Integration/Models
```

Expected: fail because factories, columns, casts, and scopes are missing.

- [ ] **Step 3: Update composer factory autoload**

Modify `packages/form-builder/composer.json` so factories match other Capell packages:

```json
"autoload": {
    "psr-4": {
        "Capell\\FormBuilder\\": "src/",
        "Capell\\FormBuilder\\Database\\Factories\\": "database/factories"
    }
},
"autoload-dev": {
    "psr-4": {
        "Capell\\FormBuilder\\Tests\\": "tests/"
    }
}
```

Run:

```bash
composer dump-autoload
```

Expected: autoload files regenerate successfully.

- [ ] **Step 4: Update migrations**

Replace `packages/form-builder/database/migrations/create_form-builder_table.php` with:

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
        Schema::create('form-builder', function (Blueprint $table): void {
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
        Schema::dropIfExists('form-builder');
    }
};
```

Replace `packages/form-builder/database/migrations/create_submissions_table.php` with:

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
            $table->foreignId('form_id')->constrained('form-builder')->cascadeOnDelete();
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

Update `packages/form-builder/src/Models/Form.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Models;

use Capell\Core\Models\Site;
use Capell\FormBuilder\Data\FormFieldData;
use Capell\FormBuilder\Data\FormSettingsData;
use Capell\FormBuilder\Database\Factories\FormFactory;
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

Update `packages/form-builder/src/Models/Submission.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Models;

use Capell\Core\Models\Site;
use Capell\FormBuilder\Casts\EncryptedDataCast;
use Capell\FormBuilder\Data\SubmissionMetaData;
use Capell\FormBuilder\Data\SubmissionPayloadData;
use Capell\FormBuilder\Database\Factories\SubmissionFactory;
use Capell\FormBuilder\Enums\SubmissionStatus;
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

Create `packages/form-builder/database/factories/FormFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Database\Factories;

use Capell\Core\Database\Factories\SiteFactory;
use Capell\Core\Models\Site;
use Capell\FormBuilder\Models\Form;
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

Create `packages/form-builder/database/factories/SubmissionFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Database\Factories;

use Capell\Core\Models\Site;
use Capell\FormBuilder\Enums\SubmissionStatus;
use Capell\FormBuilder\Models\Form;
use Capell\FormBuilder\Models\Submission;
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
                'user_agent' => 'FormBuilder test agent',
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
vendor/bin/pest packages/form-builder/tests/Integration/Models
```

Expected: pass.

- [ ] **Step 8: Commit**

```bash
git add packages/form-builder/composer.json packages/form-builder/database/migrations/create_form-builder_table.php packages/form-builder/database/migrations/create_submissions_table.php packages/form-builder/src/Models/Form.php packages/form-builder/src/Models/Submission.php packages/form-builder/database/factories/FormFactory.php packages/form-builder/database/factories/SubmissionFactory.php packages/form-builder/tests/Integration/Models/FormModelTest.php packages/form-builder/tests/Integration/Models/SubmissionModelTest.php
git commit -m "feat: add form-builder persistence model"
```

---

## Task 3: Validation And Submission Actions

**Files:**

- Create: `packages/form-builder/src/Actions/BuildFormValidationRulesAction.php`
- Create: `packages/form-builder/src/Actions/CreateSubmissionAction.php`
- Create: `packages/form-builder/src/Events/FormSubmitted.php`
- Create: `packages/form-builder/tests/Unit/Actions/BuildFormValidationRulesActionTest.php`
- Create: `packages/form-builder/tests/Integration/Actions/CreateSubmissionActionTest.php`

- [ ] **Step 1: Write validation action tests**

Create `packages/form-builder/tests/Unit/Actions/BuildFormValidationRulesActionTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\FormBuilder\Actions\BuildFormValidationRulesAction;
use Capell\FormBuilder\Models\Form;

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

Create `packages/form-builder/tests/Integration/Actions/CreateSubmissionActionTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\FormBuilder\Actions\CreateSubmissionAction;
use Capell\FormBuilder\Data\SubmissionMetaData;
use Capell\FormBuilder\Enums\SubmissionStatus;
use Capell\FormBuilder\Events\FormSubmitted;
use Capell\FormBuilder\Models\Form;
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
vendor/bin/pest packages/form-builder/tests/Unit/Actions/BuildFormValidationRulesActionTest.php packages/form-builder/tests/Integration/Actions/CreateSubmissionActionTest.php
```

Expected: fail because actions and event do not exist.

- [ ] **Step 4: Add validation action**

Create `packages/form-builder/src/Actions/BuildFormValidationRulesAction.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Actions;

use Capell\FormBuilder\Data\FormFieldData;
use Capell\FormBuilder\Enums\FormFieldType;
use Capell\FormBuilder\Models\Form;
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

Create `packages/form-builder/src/Events/FormSubmitted.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Events;

use Capell\FormBuilder\Models\Form;
use Capell\FormBuilder\Models\Submission;
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

Create `packages/form-builder/src/Actions/CreateSubmissionAction.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Actions;

use Capell\FormBuilder\Data\FormFieldData;
use Capell\FormBuilder\Data\SubmissionMetaData;
use Capell\FormBuilder\Data\SubmissionPayloadData;
use Capell\FormBuilder\Enums\SubmissionStatus;
use Capell\FormBuilder\Events\FormSubmitted;
use Capell\FormBuilder\Models\Form;
use Capell\FormBuilder\Models\Submission;
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
vendor/bin/pest packages/form-builder/tests/Unit/Actions/BuildFormValidationRulesActionTest.php packages/form-builder/tests/Integration/Actions/CreateSubmissionActionTest.php
```

Expected: pass.

- [ ] **Step 8: Commit**

```bash
git add packages/form-builder/src/Actions/BuildFormValidationRulesAction.php packages/form-builder/src/Actions/CreateSubmissionAction.php packages/form-builder/src/Events/FormSubmitted.php packages/form-builder/tests/Unit/Actions/BuildFormValidationRulesActionTest.php packages/form-builder/tests/Integration/Actions/CreateSubmissionActionTest.php
git commit -m "feat: store form submissions"
```

---

## Task 4: Submission Status Actions

**Files:**

- Create: `packages/form-builder/src/Actions/ArchiveSubmissionAction.php`
- Create: `packages/form-builder/src/Actions/MarkSubmissionReadAction.php`
- Create: `packages/form-builder/src/Actions/MarkSubmissionSpamAction.php`
- Create: `packages/form-builder/tests/Integration/Actions/SubmissionStatusActionsTest.php`

- [ ] **Step 1: Write status action tests**

Create `packages/form-builder/tests/Integration/Actions/SubmissionStatusActionsTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\FormBuilder\Actions\ArchiveSubmissionAction;
use Capell\FormBuilder\Actions\MarkSubmissionReadAction;
use Capell\FormBuilder\Actions\MarkSubmissionSpamAction;
use Capell\FormBuilder\Enums\SubmissionStatus;
use Capell\FormBuilder\Models\Submission;

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
vendor/bin/pest packages/form-builder/tests/Integration/Actions/SubmissionStatusActionsTest.php
```

Expected: fail because the status actions do not exist.

- [ ] **Step 3: Add status actions**

Create `packages/form-builder/src/Actions/MarkSubmissionReadAction.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Actions;

use Capell\FormBuilder\Enums\SubmissionStatus;
use Capell\FormBuilder\Models\Submission;
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

Create `packages/form-builder/src/Actions/ArchiveSubmissionAction.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Actions;

use Capell\FormBuilder\Enums\SubmissionStatus;
use Capell\FormBuilder\Models\Submission;
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

Create `packages/form-builder/src/Actions/MarkSubmissionSpamAction.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Actions;

use Capell\FormBuilder\Enums\SubmissionStatus;
use Capell\FormBuilder\Models\Submission;
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
vendor/bin/pest packages/form-builder/tests/Integration/Actions/SubmissionStatusActionsTest.php
```

Expected: pass.

- [ ] **Step 5: Commit**

```bash
git add packages/form-builder/src/Actions/ArchiveSubmissionAction.php packages/form-builder/src/Actions/MarkSubmissionReadAction.php packages/form-builder/src/Actions/MarkSubmissionSpamAction.php packages/form-builder/tests/Integration/Actions/SubmissionStatusActionsTest.php
git commit -m "feat: add submission status actions"
```

---

## Task 5: Package Provider, Resources, And Translations

**Files:**

- Modify: `packages/form-builder/src/Providers/FormBuilderServiceProvider.php`
- Create: `packages/form-builder/config/capell-form-builder.php`
- Create: `packages/form-builder/src/Enums/ResourceEnum.php`
- Create: `packages/form-builder/src/Enums/LivewireComponentEnum.php`
- Create: `packages/form-builder/resources/lang/en/form.php`
- Create: `packages/form-builder/resources/lang/en/generic.php`
- Create: `packages/form-builder/resources/lang/en/messages.php`
- Create: `packages/form-builder/resources/lang/en/navigation.php`
- Create: `packages/form-builder/resources/lang/en/package.php`
- Create: `packages/form-builder/resources/lang/en/table.php`

- [ ] **Step 1: Add translation files**

Create `packages/form-builder/resources/lang/en/generic.php`:

```php
<?php

declare(strict_types=1);

return [
    'form-builder' => 'FormBuilder',
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

Create `packages/form-builder/resources/lang/en/form.php`:

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

Create `packages/form-builder/resources/lang/en/navigation.php`:

```php
<?php

declare(strict_types=1);

return [
    'form-builder' => 'FormBuilder',
    'inbox' => 'Inbox',
];
```

Create `packages/form-builder/resources/lang/en/messages.php`:

```php
<?php

declare(strict_types=1);

return [
    'success' => 'Thank you, your submission has been received.',
];
```

Create `packages/form-builder/resources/lang/en/package.php`:

```php
<?php

declare(strict_types=1);

return [
    'description' => 'Editor-managed form-builder and submissions for Capell.',
];
```

Create `packages/form-builder/resources/lang/en/table.php`:

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

Create `packages/form-builder/config/capell-form-builder.php`:

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

Create `packages/form-builder/src/Enums/ResourceEnum.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Enums;

use Capell\FormBuilder\Filament\Resources\FormBuilder\FormResource;
use Capell\FormBuilder\Filament\Resources\Submissions\SubmissionResource;

enum ResourceEnum: string
{
    case Form = FormResource::class;
    case Submission = SubmissionResource::class;
}
```

Create `packages/form-builder/src/Enums/LivewireComponentEnum.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Enums;

use Capell\Core\Enums\Attribute\Component;
use Capell\Core\Enums\Attribute\EnumAttributeHelper;
use Capell\Core\Enums\Attribute\EnumAttributeInterface;
use Capell\FormBuilder\Livewire\FormComponent;

enum LivewireComponentEnum: string implements EnumAttributeInterface
{
    use EnumAttributeHelper;

    #[Component(FormComponent::class)]
    case Form = 'capell-form-builder::form';

    public static function getComponents(): array
    {
        $attributes = self::getAllCaseAttributes(Component::class);

        return array_map(fn (?Component $attribute): ?string => $attribute?->class ?? null, $attributes);
    }
}
```

- [ ] **Step 4: Update service provider**

Modify `packages/form-builder/src/Providers/FormBuilderServiceProvider.php` to follow the Blog/Address package pattern:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Providers;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Actions\RegisterBlazeOptimizedViewsAction;
use Capell\Core\Data\VendorAssetData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\FormBuilder\Enums\LivewireComponentEnum;
use Capell\FormBuilder\Enums\ResourceEnum;
use Composer\InstalledVersions;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;

class FormBuilderServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-form-builder';

    public static string $packageName = 'capell-app/form-builder';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile()
            ->hasViews(self::$name)
            ->hasTranslations()
            ->hasMigrations([
                'create_form-builder_table',
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
            description: fn (): string => __('capell-form-builder::package.description'),
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
        Blade::componentNamespace('Capell\\FormBuilder\\View\\Components', 'capell-form-builder');
        Blade::anonymousComponentNamespace('Capell\\FormBuilder\\View\\Components');

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
vendor/bin/pest packages/form-builder/tests/Arch/FormBuilderIsolationTest.php packages/form-builder/tests/Unit/Data/FormFieldDataTest.php
```

Expected: pass. The resource enum references classes created in later tasks, so defer full package suite until Task 7.

- [ ] **Step 6: Commit**

```bash
git add packages/form-builder/config/capell-form-builder.php packages/form-builder/src/Providers/FormBuilderServiceProvider.php packages/form-builder/src/Enums/ResourceEnum.php packages/form-builder/src/Enums/LivewireComponentEnum.php packages/form-builder/resources/lang/en/form.php packages/form-builder/resources/lang/en/generic.php packages/form-builder/resources/lang/en/messages.php packages/form-builder/resources/lang/en/navigation.php packages/form-builder/resources/lang/en/package.php packages/form-builder/resources/lang/en/table.php
git commit -m "feat: register form-builder package surfaces"
```

---

## Task 6: FormBuilder Admin Resource

**Files:**

- Create: `packages/form-builder/src/Filament/Resources/FormBuilder/FormResource.php`
- Create: `packages/form-builder/src/Filament/Resources/FormBuilder/Pages/ManageFormBuilder.php`
- Create: `packages/form-builder/src/Filament/Resources/FormBuilder/Schemas/FormForm.php`
- Create: `packages/form-builder/src/Filament/Resources/FormBuilder/Tables/FormBuilderTable.php`
- Create: `packages/form-builder/tests/Feature/Filament/FormResourceTest.php`

- [ ] **Step 1: Write resource tests**

Create `packages/form-builder/tests/Feature/Filament/FormResourceTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\FormBuilder\Filament\Resources\FormBuilder\Pages\ManageFormBuilder;
use Capell\FormBuilder\Models\Form;

use function Pest\Livewire\livewire;

it('lists form-builder in the admin resource', function (): void {
    $form = Form::factory()->create(['name' => 'Contact']);

    livewire(ManageFormBuilder::class)
        ->assertCanSeeTableRecords([$form]);
});

it('creates a form with a simple schema', function (): void {
    livewire(ManageFormBuilder::class)
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
vendor/bin/pest packages/form-builder/tests/Feature/Filament/FormResourceTest.php
```

Expected: fail because the resource classes do not exist.

- [ ] **Step 3: Add FormBuilder table**

Create `packages/form-builder/src/Filament/Resources/FormBuilder/Tables/FormBuilderTable.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Filament\Resources\FormBuilder\Tables;

use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\FormBuilder\Models\Form;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FormBuilderTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IdentifierColumn::make('id'),
                TextColumn::make('name')
                    ->label(__('capell-form-builder::form.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('handle')
                    ->label(__('capell-form-builder::form.handle'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('submissions_count')
                    ->label(__('capell-form-builder::generic.submissions'))
                    ->counts('submissions')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('capell-form-builder::form.is_active'))
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

- [ ] **Step 4: Add FormBuilder schema**

Create `packages/form-builder/src/Filament/Resources/FormBuilder/Schemas/FormForm.php` with a simple repeater-driven schema:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Filament\Resources\FormBuilder\Schemas;

use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\FormBuilder\Enums\FormFieldType;
use Filament\FormBuilder\Components\Repeater;
use Filament\FormBuilder\Components\Select;
use Filament\FormBuilder\Components\TagsInput;
use Filament\FormBuilder\Components\Textarea;
use Filament\FormBuilder\Components\TextInput;
use Filament\FormBuilder\Components\Toggle;
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
                Section::make(__('capell-form-builder::generic.form'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('capell-form-builder::form.name'))
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (?string $state, callable $set): mixed => $state ? $set('handle', Str::slug($state)) : null)
                            ->required(),
                        TextInput::make('handle')
                            ->label(__('capell-form-builder::form.handle'))
                            ->required()
                            ->alphaDash()
                            ->maxLength(120),
                        Textarea::make('description')
                            ->label(__('capell-form-builder::form.description'))
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label(__('capell-form-builder::form.is_active'))
                            ->default(true),
                    ]),
                Section::make(__('capell-form-builder::generic.form-builder'))
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
                Section::make(__('capell-form-builder::generic.submission'))
                    ->schema([
                        Textarea::make('settings.success_message')
                            ->label(__('capell-form-builder::form.success_message'))
                            ->default(fn (): string => __('capell-form-builder::messages.success')),
                        Toggle::make('settings.store_submissions')
                            ->label(__('capell-form-builder::form.store_submissions'))
                            ->default(true),
                        TextInput::make('settings.notification_email')
                            ->label(__('capell-form-builder::form.notification_email'))
                            ->email(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
```

- [ ] **Step 5: Add resource and page**

Create `packages/form-builder/src/Filament/Resources/FormBuilder/FormResource.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Filament\Resources\FormBuilder;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasConfiguredForm;
use Capell\Admin\Filament\Concerns\HasConfiguredTable;
use Capell\Core\Facades\CapellCore;
use Capell\FormBuilder\Filament\Resources\FormBuilder\Pages\ManageFormBuilder;
use Capell\FormBuilder\Filament\Resources\FormBuilder\Schemas\FormForm;
use Capell\FormBuilder\Filament\Resources\FormBuilder\Tables\FormBuilderTable;
use Capell\FormBuilder\Models\Form;
use Capell\FormBuilder\Providers\FormBuilderServiceProvider;
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

    protected static string $tableConfigurator = FormBuilderTable::class;

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
        return (string) __('capell-form-builder::navigation.form-builder');
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) __('capell-form-builder::navigation.form-builder');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::getPackage(FormBuilderServiceProvider::$packageName)->isInstalled();
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFormBuilder::route('/'),
        ];
    }
}
```

Create `packages/form-builder/src/Filament/Resources/FormBuilder/Pages/ManageFormBuilder.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Filament\Resources\FormBuilder\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\FormBuilder\Enums\ResourceEnum;
use Capell\FormBuilder\Filament\Resources\FormBuilder\FormResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Override;

class ManageFormBuilder extends ManageRecords
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
vendor/bin/pest packages/form-builder/tests/Feature/Filament/FormResourceTest.php
```

Expected: pass.

- [ ] **Step 7: Commit**

```bash
git add packages/form-builder/src/Filament/Resources/FormBuilder packages/form-builder/tests/Feature/Filament/FormResourceTest.php
git commit -m "feat: add form-builder admin resource"
```

---

## Task 7: Submission Inbox Resource

**Files:**

- Create: `packages/form-builder/src/Filament/Resources/Submissions/SubmissionResource.php`
- Create: `packages/form-builder/src/Filament/Resources/Submissions/Pages/ListSubmissions.php`
- Create: `packages/form-builder/src/Filament/Resources/Submissions/Pages/ViewSubmission.php`
- Create: `packages/form-builder/src/Filament/Resources/Submissions/Schemas/SubmissionInfolist.php`
- Create: `packages/form-builder/src/Filament/Resources/Submissions/Tables/SubmissionsTable.php`
- Create: `packages/form-builder/tests/Feature/Filament/SubmissionResourceTest.php`

- [ ] **Step 1: Write inbox tests**

Create `packages/form-builder/tests/Feature/Filament/SubmissionResourceTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\FormBuilder\Enums\SubmissionStatus;
use Capell\FormBuilder\Filament\Resources\Submissions\Pages\ListSubmissions;
use Capell\FormBuilder\Models\Submission;

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
vendor/bin/pest packages/form-builder/tests/Feature/Filament/SubmissionResourceTest.php
```

Expected: fail because the submission resource classes do not exist.

- [ ] **Step 3: Add submissions table**

Create `packages/form-builder/src/Filament/Resources/Submissions/Tables/SubmissionsTable.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Filament\Resources\Submissions\Tables;

use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\FormBuilder\Actions\ArchiveSubmissionAction;
use Capell\FormBuilder\Actions\MarkSubmissionReadAction;
use Capell\FormBuilder\Actions\MarkSubmissionSpamAction;
use Capell\FormBuilder\Enums\SubmissionStatus;
use Capell\FormBuilder\Models\Submission;
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
                    ->label(__('capell-form-builder::table.form'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('capell-form-builder::table.status'))
                    ->badge(),
                TextColumn::make('payload.values')
                    ->label(__('capell-form-builder::table.payload'))
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
                    ->label(__('capell-form-builder::generic.submission_status.read'))
                    ->action(fn (Submission $record): Submission => MarkSubmissionReadAction::run($record)),
                Action::make('archive')
                    ->label(__('capell-form-builder::generic.submission_status.archived'))
                    ->action(fn (Submission $record): Submission => ArchiveSubmissionAction::run($record)),
                Action::make('spam')
                    ->label(__('capell-form-builder::generic.submission_status.spam'))
                    ->color('danger')
                    ->action(fn (Submission $record): Submission => MarkSubmissionSpamAction::run($record)),
            ]);
    }
}
```

- [ ] **Step 4: Add resource, list page, and view page**

Create `packages/form-builder/src/Filament/Resources/Submissions/SubmissionResource.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Filament\Resources\Submissions;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasConfiguredTable;
use Capell\Core\Facades\CapellCore;
use Capell\FormBuilder\Filament\Resources\Submissions\Pages\ListSubmissions;
use Capell\FormBuilder\Filament\Resources\Submissions\Pages\ViewSubmission;
use Capell\FormBuilder\Filament\Resources\Submissions\Tables\SubmissionsTable;
use Capell\FormBuilder\Models\Submission;
use Capell\FormBuilder\Providers\FormBuilderServiceProvider;
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
        return (string) __('capell-form-builder::navigation.inbox');
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) __('capell-form-builder::navigation.form-builder');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::getPackage(FormBuilderServiceProvider::$packageName)->isInstalled();
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

Create `packages/form-builder/src/Filament/Resources/Submissions/Pages/ListSubmissions.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Filament\Resources\Submissions\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\FormBuilder\Enums\ResourceEnum;
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

Create `packages/form-builder/src/Filament/Resources/Submissions/Schemas/SubmissionInfolist.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Filament\Resources\Submissions\Schemas;

use Capell\FormBuilder\Models\Submission;
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
                Section::make(__('capell-form-builder::generic.submission'))
                    ->schema([
                        TextEntry::make('form.name')->label(__('capell-form-builder::table.form')),
                        TextEntry::make('status')->badge()->label(__('capell-form-builder::table.status')),
                        TextEntry::make('submitted_at')->dateTime()->label(__('capell-form-builder::table.submitted_at')),
                    ]),
                Section::make(__('capell-form-builder::table.payload'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('payload.values')
                            ->hiddenLabel()
                            ->getStateUsing(fn (Submission $record): string => collect($record->payload->values)
                                ->map(fn (mixed $value, string $key): string => "{$key}: {$value}")
                                ->join(PHP_EOL))
                            ->prose(),
                    ]),
                Section::make(__('capell-form-builder::table.meta'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('meta.ipAddress')->label(__('capell-form-builder::table.ip_address')),
                        TextEntry::make('meta.userAgent')->label(__('capell-form-builder::table.user_agent')),
                        TextEntry::make('meta.url')->label(__('capell-form-builder::table.url')),
                        TextEntry::make('meta.referer')->label(__('capell-form-builder::table.referer')),
                    ]),
            ]);
    }
}
```

Create `packages/form-builder/src/Filament/Resources/Submissions/Pages/ViewSubmission.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Filament\Resources\Submissions\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\FormBuilder\Enums\ResourceEnum;
use Capell\FormBuilder\Filament\Resources\Submissions\Schemas\SubmissionInfolist;
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
vendor/bin/pest packages/form-builder/tests/Feature/Filament/SubmissionResourceTest.php
```

Expected: pass.

- [ ] **Step 6: Commit**

```bash
git add packages/form-builder/src/Filament/Resources/Submissions packages/form-builder/tests/Feature/Filament/SubmissionResourceTest.php
git commit -m "feat: add form-builder inbox resource"
```

---

## Task 8: Frontend Blade And Livewire Form

**Files:**

- Create: `packages/form-builder/src/View/Components/Form.php`
- Create: `packages/form-builder/src/Livewire/FormComponent.php`
- Create: `packages/form-builder/resources/views/components/form.blade.php`
- Create: `packages/form-builder/resources/views/livewire/form.blade.php`
- Create: `packages/form-builder/resources/views/fields/checkbox.blade.php`
- Create: `packages/form-builder/resources/views/fields/email.blade.php`
- Create: `packages/form-builder/resources/views/fields/honeypot.blade.php`
- Create: `packages/form-builder/resources/views/fields/hidden.blade.php`
- Create: `packages/form-builder/resources/views/fields/select.blade.php`
- Create: `packages/form-builder/resources/views/fields/text.blade.php`
- Create: `packages/form-builder/resources/views/fields/textarea.blade.php`
- Create: `packages/form-builder/tests/Feature/Livewire/FormComponentTest.php`

- [ ] **Step 1: Write frontend tests**

Create `packages/form-builder/tests/Feature/Livewire/FormComponentTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\FormBuilder\Livewire\FormComponent;
use Capell\FormBuilder\Models\Form;

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
vendor/bin/pest packages/form-builder/tests/Feature/Livewire/FormComponentTest.php
```

Expected: fail because the component and views do not exist.

- [ ] **Step 3: Add Blade wrapper component**

Create `packages/form-builder/src/View/Components/Form.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\View\Components;

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
        return view('capell-form-builder::components.form');
    }
}
```

Create `packages/form-builder/resources/views/components/form.blade.php`:

```blade
<livewire:capell-form-builder::form :handle="$handle" />
```

- [ ] **Step 4: Add Livewire component**

Create `packages/form-builder/src/Livewire/FormComponent.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Livewire;

use Capell\FormBuilder\Actions\CreateSubmissionAction;
use Capell\FormBuilder\Data\SubmissionMetaData;
use Capell\FormBuilder\Models\Form;
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
        return view('capell-form-builder::livewire.form', [
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

Create `packages/form-builder/resources/views/livewire/form.blade.php`:

```blade
<form wire:submit="submit">
    @if ($submitted)
        <p>
            {{ $form->settings->successMessage ?? __('capell-form-builder::messages.success') }}
        </p>
    @else
        @foreach ($form->schema as $field)
            @includeIf("capell-form-builder::fields.{$field->type->value}", ['field' => $field])
        @endforeach

        <button type="submit">
            {{ __('capell-form-builder::generic.submit') }}
        </button>
    @endif
</form>
```

Create `packages/form-builder/resources/views/fields/email.blade.php`:

```blade
<label>
    <span>{{ $field->label }}</span>
    <input
        type="email"
        wire:model.defer="state.{{ $field->key }}"
        placeholder="{{ $field->placeholder }}"
    />
</label>
@error($field->key)
    <p>{{ $message }}</p>
@enderror
```

Create `packages/form-builder/resources/views/fields/text.blade.php`:

```blade
<label>
    <span>{{ $field->label }}</span>
    <input
        type="text"
        wire:model.defer="state.{{ $field->key }}"
        placeholder="{{ $field->placeholder }}"
    />
</label>
@error($field->key)
    <p>{{ $message }}</p>
@enderror
```

Create `packages/form-builder/resources/views/fields/textarea.blade.php`:

```blade
<label>
    <span>{{ $field->label }}</span>
    <textarea
        wire:model.defer="state.{{ $field->key }}"
        placeholder="{{ $field->placeholder }}"
    ></textarea>
</label>
@error($field->key)
    <p>{{ $message }}</p>
@enderror
```

Create `packages/form-builder/resources/views/fields/select.blade.php`:

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

Create `packages/form-builder/resources/views/fields/checkbox.blade.php`:

```blade
<label>
    <input
        type="checkbox"
        wire:model.defer="state.{{ $field->key }}"
    />
    <span>{{ $field->label }}</span>
</label>
@error($field->key)
    <p>{{ $message }}</p>
@enderror
```

Create `packages/form-builder/resources/views/fields/hidden.blade.php`:

```blade
<input
    type="hidden"
    wire:model.defer="state.{{ $field->key }}"
/>
```

Create `packages/form-builder/resources/views/fields/honeypot.blade.php`:

```blade
<div
    class="hidden"
    aria-hidden="true"
>
    <label>
        <span>{{ $field->label }}</span>
        <input
            type="text"
            tabindex="-1"
            autocomplete="off"
            wire:model.defer="state.{{ $field->key }}"
        />
    </label>
</div>
```

- [ ] **Step 6: Run frontend tests**

Run:

```bash
vendor/bin/pest packages/form-builder/tests/Feature/Livewire/FormComponentTest.php
```

Expected: pass.

- [ ] **Step 7: Commit**

```bash
git add packages/form-builder/src/View/Components/Form.php packages/form-builder/src/Livewire/FormComponent.php packages/form-builder/resources/views/components/form.blade.php packages/form-builder/resources/views/livewire/form.blade.php packages/form-builder/resources/views/fields packages/form-builder/resources/lang/en/generic.php packages/form-builder/tests/Feature/Livewire/FormComponentTest.php
git commit -m "feat: add frontend form component"
```

---

## Task 9: README, Package Tests, And Preflight

**Files:**

- Modify: `packages/form-builder/README.md`

- [ ] **Step 1: Update README**

Replace `packages/form-builder/README.md` with concise package documentation:

````markdown
# Capell FormBuilder

FormBuilder adds editor-managed form-builder and a submission inbox to Capell.

## Install

```bash
composer require capell-app/form-builder
```
````

Run the host application's normal migration/deployment flow after installation.

## Render a form

```blade
<x-capell-form-builder::form handle="contact" />
```

## Admin

- FormBuilder: create and manage simple form definitions.
- Inbox: review submitted form payloads and mark submissions as read, archived, or spam.

## Extension points

- Override package views to change frontend markup.
- Listen for `Capell\FormBuilder\Events\FormSubmitted` to send email, Slack messages, CRM syncs, or webhooks.
- Keep business behavior in project listeners or Actions rather than editing package views.

````

- [ ] **Step 2: Run the package suite**

Run:

```bash
vendor/bin/pest packages/form-builder/tests
````

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
git add packages/form-builder/README.md
git commit -m "docs: document form-builder package"
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
        "form-builder",
        "layout-builder",
        "laravel",
        "cms"
    ],
    "license": "proprietary",
    "require": {
        "php": "^8.2",
        "capell-app/form-builder": "*",
        "capell-app/layout-builder": "*"
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
    "requires": ["capell-app/form-builder", "capell-app/layout-builder"],
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

it('declares form-builder and layout-builder as package requirements', function (): void {
    $manifest = json_decode(
        file_get_contents(__DIR__ . '/../../capell.json'),
        associative: true,
    );

    expect($manifest['requires'])->toContain('capell-app/form-builder')
        ->and($manifest['requires'])->toContain('capell-app/layout-builder');
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
    'description' => 'Newsletter signup widgets powered by FormBuilder and LayoutBuilder.',
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
use Capell\FormBuilder\Events\FormSubmitted;
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

use Capell\FormBuilder\Models\Form;
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

- [ ] **Step 6: Create newsletter LayoutBuilder widget action**

Create `packages/newsletter/src/Actions/CreateNewsletterSignupWidgetAction.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Newsletter\Actions;

use Capell\Core\Models\Type;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Enums\WidgetTypeEnum;
use Capell\LayoutBuilder\Filament\Configurators\Types\WidgetTypeConfigurator;
use Capell\LayoutBuilder\Models\Widget;
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

use Capell\LayoutBuilder\Filament\Configurators\Widgets\DefaultWidgetConfigurator;
use Filament\FormBuilder\Components\Textarea;
use Filament\FormBuilder\Components\TextInput;
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
                    ->label(__('capell-form-builder::form.handle'))
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

    <x-capell-form-builder::form :handle="$formHandle" />
</section>
```

- [ ] **Step 9: Add newsletter event**

Create `packages/newsletter/src/Events/NewsletterSignupSubmitted.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Newsletter\Events;

use Capell\FormBuilder\Models\Submission;
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

use Capell\FormBuilder\Events\FormSubmitted;
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
use Capell\FormBuilder\Providers\FormBuilderServiceProvider;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\LayoutBuilder\Providers\LayoutBuilderServiceProvider;
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
            FormBuilderServiceProvider::class,
            LayoutBuilderServiceProvider::class,
            NewsletterServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::registerPackage(FormBuilderServiceProvider::$packageName, path: realpath(__DIR__ . '/../../form-builder'));
        CapellCore::registerPackage(LayoutBuilderServiceProvider::$packageName, path: realpath(__DIR__ . '/../../layout-builder'));
        CapellCore::registerPackage(NewsletterServiceProvider::$packageName, path: realpath(__DIR__ . '/..'));

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(FrontendServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(FormBuilderServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(LayoutBuilderServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(NewsletterServiceProvider::$packageName);
    }
}
```

Create `packages/newsletter/tests/Integration/CreateNewsletterSignupWidgetActionTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\FormBuilder\Models\Form;
use Capell\LayoutBuilder\Models\Widget;
use Capell\Newsletter\Actions\CreateNewsletterSignupFormAction;
use Capell\Newsletter\Actions\CreateNewsletterSignupWidgetAction;

it('creates the default newsletter signup form', function (): void {
    $form = CreateNewsletterSignupFormAction::run();

    expect($form)->toBeInstanceOf(Form::class)
        ->and($form->handle)->toBe('newsletter-signup')
        ->and($form->schema->first()->key)->toBe('email');
});

it('creates the newsletter signup layout-builder widget', function (): void {
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

- The plan implements every spec item: editor-managed form-builder, compact field types, frontend rendering, validation, stored submissions, inbox workflows, events, translations, docs, and tests.
- The Newsletter package is separate from FormBuilder and depends on both FormBuilder and LayoutBuilder for the signup widget.
- The plan keeps excluded v1 features out: uploads, payments, multi-step form-builder, insights, conditional logic, and CRM/webhook delivery.
- The plan uses Capell conventions: strict types, Actions, Data objects, enums for persisted values, package translations, Filament labels via methods/configurators, and direct package test commands.
- The plan does not modify unrelated packages.
- The final implementation must run `vendor/bin/pest packages/form-builder/tests` and `composer preflight` before merge or PR.
