# Events Package Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build `capell-app/events`, a Capell package for pageable event content with recurring occurrences, upcoming listings, a Livewire calendar, booking links, iCalendar feeds, and event JSON-LD schema.

**Architecture:** Follow Blog’s package pattern: Event is the pageable content model, EventOccurrence is the query-optimized schedule model, Actions own all writes and derived output, Data classes own structured boundaries, and Livewire page components own frontend calendar/listing interactions. Recurrence rules are authored on Event and expanded into persisted occurrence rows for fast listings, feeds, and schema output.

**Tech Stack:** PHP 8.2, Laravel, Capell Core/Admin/Frontend, Filament, Livewire, Spatie Laravel Data, Lorisleiva Actions, Mosaic layouts, Pest.

---

## File Structure

### Modify

- `composer.json` - add `Capell\Events\` and `Capell\Events\Database\Factories\` autoload entries plus `Capell\Events\Tests\` dev autoload.
- `tests/Pest.php` - register `packages/events/tests` with `EventsTestCase`.
- `tests/Packages/PackagesTestCase.php` - load the events providers and force package installation for package integration tests.

### Create

- `packages/events/README.md`
- `packages/events/capell.json`
- `packages/events/composer.json`
- `packages/events/config/capell-events.php`
- `packages/events/database/factories/EventFactory.php`
- `packages/events/database/factories/EventOccurrenceFactory.php`
- `packages/events/database/migrations/create_events_table.php`
- `packages/events/database/migrations/create_event_occurrences_table.php`
- `packages/events/resources/lang/en/form.php`
- `packages/events/resources/lang/en/generic.php`
- `packages/events/resources/lang/en/messages.php`
- `packages/events/resources/lang/en/navigation.php`
- `packages/events/resources/lang/en/package.php`
- `packages/events/resources/lang/en/table.php`
- `packages/events/resources/views/components/event-schema.blade.php`
- `packages/events/resources/views/livewire/page/calendar.blade.php`
- `packages/events/resources/views/livewire/page/events.blade.php`
- `packages/events/routes/web.php`
- `packages/events/src/Actions/BuildEventCalendarMonthAction.php`
- `packages/events/src/Actions/BuildEventSchemaAction.php`
- `packages/events/src/Actions/BuildIcsFeedAction.php`
- `packages/events/src/Actions/DeleteFutureEventOccurrencesAction.php`
- `packages/events/src/Actions/GenerateEventOccurrencesAction.php`
- `packages/events/src/Actions/GetEventLayoutAction.php`
- `packages/events/src/Actions/InstallPackageAction.php`
- `packages/events/src/Actions/SyncEventOccurrencesAction.php`
- `packages/events/src/Console/Commands/InstallCommand.php`
- `packages/events/src/Console/Commands/SetupCommand.php`
- `packages/events/src/Data/EventBookingData.php`
- `packages/events/src/Data/EventCalendarDayData.php`
- `packages/events/src/Data/EventLocationData.php`
- `packages/events/src/Data/EventRecurrenceData.php`
- `packages/events/src/Data/EventScheduleData.php`
- `packages/events/src/Data/EventSchemaData.php`
- `packages/events/src/Enums/EventLocationTypeEnum.php`
- `packages/events/src/Enums/EventOccurrenceStatusEnum.php`
- `packages/events/src/Enums/EventPageTypeEnum.php`
- `packages/events/src/Enums/EventRecurrenceFrequencyEnum.php`
- `packages/events/src/Enums/EventsLayoutEnum.php`
- `packages/events/src/Enums/EventsTypeGroupEnum.php`
- `packages/events/src/Enums/LivewirePageComponentEnum.php`
- `packages/events/src/Enums/ResourceEnum.php`
- `packages/events/src/Filament/Configurators/Events/EventPageConfigurator.php`
- `packages/events/src/Filament/Resources/Events/EventResource.php`
- `packages/events/src/Filament/Resources/Events/Pages/CreateEvent.php`
- `packages/events/src/Filament/Resources/Events/Pages/EditEvent.php`
- `packages/events/src/Filament/Resources/Events/Pages/ListEvents.php`
- `packages/events/src/Filament/Resources/Events/Schemas/EventForm.php`
- `packages/events/src/Filament/Resources/Events/Tables/EventPagesTable.php`
- `packages/events/src/Livewire/Page/Calendar.php`
- `packages/events/src/Livewire/Page/Events.php`
- `packages/events/src/Models/Event.php`
- `packages/events/src/Models/EventOccurrence.php`
- `packages/events/src/Observers/EventObserver.php`
- `packages/events/src/Providers/AdminServiceProvider.php`
- `packages/events/src/Providers/ConsoleServiceProvider.php`
- `packages/events/src/Providers/EventsServiceProvider.php`
- `packages/events/src/Providers/FrontendServiceProvider.php`
- `packages/events/src/Support/Creator/EventsCreator.php`
- `packages/events/src/Support/EventsModelRegistrar.php`
- `packages/events/src/Support/Loader/EventLoader.php`
- `packages/events/src/View/Components/EventSchema.php`
- `packages/events/tests/Arch/EventsPackageTest.php`
- `packages/events/tests/EventsTestCase.php`
- `packages/events/tests/Feature/CalendarPageTest.php`
- `packages/events/tests/Feature/EventFeedTest.php`
- `packages/events/tests/Feature/EventPageTest.php`
- `packages/events/tests/Feature/EventsPageTest.php`
- `packages/events/tests/Feature/Filament/EventResourceTest.php`
- `packages/events/tests/Integration/Actions/BuildEventCalendarMonthActionTest.php`
- `packages/events/tests/Integration/Actions/BuildEventSchemaActionTest.php`
- `packages/events/tests/Integration/Actions/BuildIcsFeedActionTest.php`
- `packages/events/tests/Integration/Actions/GenerateEventOccurrencesActionTest.php`
- `packages/events/tests/Integration/Actions/SyncEventOccurrencesActionTest.php`
- `packages/events/tests/Integration/Models/EventModelTest.php`
- `packages/events/tests/Integration/Models/EventOccurrenceModelTest.php`
- `packages/events/tests/Unit/Data/EventDataTest.php`
- `packages/events/tests/Unit/ManifestRequirementsTest.php`
- `packages/events/tests/Pest.php`

---

## Task 0: Worktree Preparation

**Files:**

- Read: repository status only.

- [ ] **Step 1: Check for unresolved files before package implementation**

Run:

```bash
git status --short --untracked-files=all
git diff --name-only --diff-filter=U
```

Expected: no unresolved files. If `packages/workspaces/tests/Integration/WorkspaceSchedulerMetadataActionTest.php` is still unresolved, stop before implementation and ask Ben whether to resolve, stash, or move the Events work to a clean worktree.

- [ ] **Step 2: Confirm docs-only plan files are present**

Run:

```bash
test -f docs/superpowers/specs/2026-05-01-events-package-design.md
test -f docs/superpowers/plans/2026-05-01-events-package.md
```

Expected: both commands exit with status `0`.

---

## Task 1: Package Skeleton And Registration

**Files:**

- Create: `packages/events/composer.json`
- Create: `packages/events/capell.json`
- Create: `packages/events/config/capell-events.php`
- Create: `packages/events/README.md`
- Create: `packages/events/resources/lang/en/package.php`
- Create: `packages/events/src/Providers/EventsServiceProvider.php`
- Create: `packages/events/src/Providers/AdminServiceProvider.php`
- Create: `packages/events/src/Providers/FrontendServiceProvider.php`
- Create: `packages/events/src/Providers/ConsoleServiceProvider.php`
- Create: `packages/events/tests/EventsTestCase.php`
- Create: `packages/events/tests/Pest.php`
- Create: `packages/events/tests/Unit/ManifestRequirementsTest.php`
- Modify: `composer.json`
- Modify: `tests/Pest.php`
- Modify: `tests/Packages/PackagesTestCase.php`

- [ ] **Step 1: Write the manifest test**

Create `packages/events/tests/Unit/ManifestRequirementsTest.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Arr;

it('declares the events package metadata', function (): void {
    $manifest = json_decode((string) file_get_contents(__DIR__ . '/../../../capell.json'), true);

    expect($manifest['name'])->toBe('capell-app/events')
        ->and($manifest['kind'])->toBe('package')
        ->and($manifest['contexts'])->toBe(['admin', 'frontend', 'console'])
        ->and($manifest['providers']['shared'])->toContain('Capell\\Events\\Providers\\EventsServiceProvider')
        ->and($manifest['providers']['admin'])->toContain('Capell\\Events\\Providers\\AdminServiceProvider')
        ->and($manifest['providers']['frontend'])->toContain('Capell\\Events\\Providers\\FrontendServiceProvider')
        ->and($manifest['providers']['console'])->toContain('Capell\\Events\\Providers\\ConsoleServiceProvider')
        ->and(Arr::get($manifest, 'commands.install'))->toBe('capell:events-install')
        ->and($manifest['requires'])->toContain('capell-app/mosaic');
});
```

- [ ] **Step 2: Run the manifest test and verify failure**

Run:

```bash
vendor/bin/pest packages/events/tests/Unit/ManifestRequirementsTest.php
```

Expected: fail because `packages/events` does not exist.

- [ ] **Step 3: Add package metadata**

Create `packages/events/composer.json`:

```json
{
    "name": "capell-app/events",
    "description": "Events for Capell",
    "keywords": ["capell", "events", "laravel", "filamentphp", "cms"],
    "homepage": "https://github.com/capell-app/events",
    "license": "proprietary",
    "authors": [
        {
            "name": "Howdu",
            "email": "cms.multi2@gmail.com",
            "role": "Developer"
        }
    ],
    "require": {
        "php": "^8.2",
        "capell-app/admin": "*",
        "capell-app/frontend": "*",
        "capell-app/mosaic": "*",
        "capell-app/navigation": "*",
        "capell-app/workspaces": "*"
    },
    "autoload": {
        "psr-4": {
            "Capell\\Events\\": "src/",
            "Capell\\Events\\Database\\Factories\\": "database/factories"
        }
    },
    "extra": {
        "laravel": {
            "providers": ["Capell\\Events\\Providers\\EventsServiceProvider"]
        }
    },
    "config": {
        "sort-packages": true
    },
    "prefer-stable": true
}
```

Create `packages/events/capell.json`:

```json
{
    "name": "capell-app/events",
    "kind": "package",
    "capell-version": "^4.0",
    "contexts": ["admin", "frontend", "console"],
    "requires": [
        "capell-app/core",
        "capell-app/admin",
        "capell-app/frontend",
        "capell-app/mosaic",
        "capell-app/navigation",
        "capell-app/workspaces"
    ],
    "providers": {
        "shared": ["Capell\\Events\\Providers\\EventsServiceProvider"],
        "admin": ["Capell\\Events\\Providers\\AdminServiceProvider"],
        "frontend": ["Capell\\Events\\Providers\\FrontendServiceProvider"],
        "console": ["Capell\\Events\\Providers\\ConsoleServiceProvider"]
    },
    "commands": {
        "install": "capell:events-install",
        "setup": "capell:events-setup",
        "setupParams": ["sites", "languages"]
    }
}
```

Create `packages/events/config/capell-events.php`:

```php
<?php

declare(strict_types=1);

return [
    'default_generation_months' => 18,
    'feed_months' => 12,
    'pagination_limit' => 12,
];
```

Create `packages/events/README.md`:

```markdown
# Capell Events

Events package for Capell CMS. Provides pageable event detail pages, upcoming event listings, recurring occurrence generation, an interactive Livewire calendar, booking links, iCalendar feeds, and event schema output.

Install Mosaic before installing Events.
```

- [ ] **Step 4: Add language package description**

Create `packages/events/resources/lang/en/package.php`:

```php
<?php

declare(strict_types=1);

return [
    'description' => 'Create and publish event pages, listings, calendars, booking links, and calendar feeds.',
];
```

- [ ] **Step 5: Add provider stubs**

Create `packages/events/src/Providers/EventsServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Events\Providers;

use Capell\Core\Data\VendorAssetData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Composer\InstalledVersions;
use Spatie\LaravelPackageTools\Package;

class EventsServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-events';

    public static string $packageName = 'capell-app/events';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile()
            ->hasViews(self::$name)
            ->hasTranslations()
            ->hasRoute('web');
    }

    public function registeringPackage(): void
    {
        $this
            ->registerPackageMetadata()
            ->registerPackageAssets();
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            version: $this->getVersion(),
            permissions: [
                'create_event',
                'replicate_event',
                'restore_any_event',
                'restore_event',
                'update_event',
                'view_any_event',
                'view_event',
            ],
            description: fn (): string => __('capell-events::package.description'),
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

    private function getVersion(): string
    {
        if (! class_exists(InstalledVersions::class) || ! InstalledVersions::isInstalled(static::$packageName)) {
            return 'dev';
        }

        return InstalledVersions::getPrettyVersion(static::$packageName) ?? 'dev';
    }
}
```

Create `packages/events/src/Providers/AdminServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Events\Providers;

use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
    }
}
```

Create `packages/events/src/Providers/FrontendServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Events\Providers;

use Illuminate\Support\ServiceProvider;

final class FrontendServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
    }
}
```

Create `packages/events/src/Providers/ConsoleServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Events\Providers;

use Illuminate\Support\ServiceProvider;

final class ConsoleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
    }
}
```

- [ ] **Step 6: Add events test case and Pest registration**

Create `packages/events/tests/EventsTestCase.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Events\Tests;

use Capell\Events\Providers\AdminServiceProvider;
use Capell\Events\Providers\ConsoleServiceProvider;
use Capell\Events\Providers\EventsServiceProvider;
use Capell\Events\Providers\FrontendServiceProvider;
use Capell\Tests\AbstractTestCase;

class EventsTestCase extends AbstractTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            EventsServiceProvider::class,
            AdminServiceProvider::class,
            FrontendServiceProvider::class,
            ConsoleServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
```

Create `packages/events/tests/Pest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Events\Tests\EventsTestCase;

pest()->extend(EventsTestCase::class)->in('Feature', 'Integration', 'Unit');
```

Modify root `tests/Pest.php` by adding:

```php
use Capell\Events\Tests\EventsTestCase;

pest()->extend(EventsTestCase::class)->in('../packages/events/tests');
```

- [ ] **Step 7: Add root autoload entries**

Modify root `composer.json` PSR-4 autoload blocks:

```json
"Capell\\Events\\": "packages/events/src",
"Capell\\Events\\Database\\Factories\\": "packages/events/database/factories"
```

Modify root `autoload-dev`:

```json
"Capell\\Events\\Tests\\": "packages/events/tests"
```

Run:

```bash
composer dump-autoload
```

Expected: autoload generation completes without errors.

- [ ] **Step 8: Run the manifest test and commit**

Run:

```bash
vendor/bin/pest packages/events/tests/Unit/ManifestRequirementsTest.php
```

Expected: pass.

Commit:

```bash
git add composer.json tests/Pest.php tests/Packages/PackagesTestCase.php packages/events
git commit -m "feat: scaffold events package"
```

---

## Task 2: Data Objects, Enums, Migrations, And Models

**Files:**

- Create: event data, enum, migration, factory, model, and observer files listed in File Structure.
- Test: `packages/events/tests/Unit/Data/EventDataTest.php`
- Test: `packages/events/tests/Integration/Models/EventModelTest.php`
- Test: `packages/events/tests/Integration/Models/EventOccurrenceModelTest.php`

- [ ] **Step 1: Write data tests**

Create `packages/events/tests/Unit/Data/EventDataTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Events\Data\EventBookingData;
use Capell\Events\Data\EventLocationData;
use Capell\Events\Data\EventRecurrenceData;
use Capell\Events\Data\EventScheduleData;
use Capell\Events\Enums\EventLocationTypeEnum;
use Capell\Events\Enums\EventRecurrenceFrequencyEnum;
use Carbon\CarbonImmutable;

it('creates schedule data with recurrence', function (): void {
    $schedule = EventScheduleData::from([
        'starts_at' => '2026-06-01 09:00:00',
        'ends_at' => '2026-06-01 10:30:00',
        'timezone' => 'Europe/London',
        'recurrence' => [
            'frequency' => 'weekly',
            'interval' => 1,
            'weekdays' => ['monday'],
            'month_day' => null,
            'until' => '2026-07-01',
            'count' => null,
        ],
        'generate_until' => '2026-07-01',
    ]);

    expect($schedule->startsAt)->toBeInstanceOf(CarbonImmutable::class)
        ->and($schedule->recurrence->frequency)->toBe(EventRecurrenceFrequencyEnum::Weekly)
        ->and($schedule->recurrence->weekdays)->toBe(['monday']);
});

it('creates physical location and booking data', function (): void {
    $location = EventLocationData::from([
        'type' => 'physical',
        'name' => 'Town Hall',
        'address' => 'Victoria Square, Birmingham',
        'url' => null,
        'latitude' => 52.479,
        'longitude' => -1.902,
    ]);

    $booking = EventBookingData::from([
        'url' => 'https://example.test/book',
        'label' => 'Book tickets',
        'opens_at' => null,
        'closes_at' => null,
    ]);

    expect($location->type)->toBe(EventLocationTypeEnum::Physical)
        ->and($booking->url)->toBe('https://example.test/book');
});
```

- [ ] **Step 2: Add enums and data classes**

Create `packages/events/src/Enums/EventRecurrenceFrequencyEnum.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Events\Enums;

use Filament\Support\Contracts\HasLabel;

enum EventRecurrenceFrequencyEnum: string implements HasLabel
{
    case None = 'none';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    public function getLabel(): string
    {
        return match ($this) {
            self::None => __('capell-events::generic.recurrence.none'),
            self::Daily => __('capell-events::generic.recurrence.daily'),
            self::Weekly => __('capell-events::generic.recurrence.weekly'),
            self::Monthly => __('capell-events::generic.recurrence.monthly'),
        };
    }
}
```

Create `packages/events/src/Enums/EventLocationTypeEnum.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Events\Enums;

use Filament\Support\Contracts\HasLabel;

enum EventLocationTypeEnum: string implements HasLabel
{
    case Physical = 'physical';
    case Online = 'online';
    case Hybrid = 'hybrid';

    public function getLabel(): string
    {
        return match ($this) {
            self::Physical => __('capell-events::generic.location_type.physical'),
            self::Online => __('capell-events::generic.location_type.online'),
            self::Hybrid => __('capell-events::generic.location_type.hybrid'),
        };
    }
}
```

Create `packages/events/src/Enums/EventOccurrenceStatusEnum.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Events\Enums;

use Filament\Support\Contracts\HasLabel;

enum EventOccurrenceStatusEnum: string implements HasLabel
{
    case Scheduled = 'scheduled';
    case Cancelled = 'cancelled';
    case Postponed = 'postponed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Scheduled => __('capell-events::generic.occurrence_status.scheduled'),
            self::Cancelled => __('capell-events::generic.occurrence_status.cancelled'),
            self::Postponed => __('capell-events::generic.occurrence_status.postponed'),
        };
    }
}
```

Create `packages/events/src/Enums/EventPageTypeEnum.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Events\Enums;

enum EventPageTypeEnum: string
{
    case Calendar = 'events-calendar';
    case Event = 'event';
    case Events = 'events';
}
```

Create `packages/events/src/Data/EventRecurrenceData.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Events\Data;

use Capell\Events\Enums\EventRecurrenceFrequencyEnum;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class EventRecurrenceData extends Data
{
    public function __construct(
        public EventRecurrenceFrequencyEnum $frequency = EventRecurrenceFrequencyEnum::None,
        public int $interval = 1,
        /** @var array<int, string> */
        public array $weekdays = [],
        public ?int $monthDay = null,
        public ?string $until = null,
        public ?int $count = null,
    ) {
    }
}
```

Create `packages/events/src/Data/EventScheduleData.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Events\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class EventScheduleData extends Data
{
    public function __construct(
        public CarbonImmutable $startsAt,
        public ?CarbonImmutable $endsAt = null,
        public string $timezone = 'UTC',
        public ?EventRecurrenceData $recurrence = null,
        public ?CarbonImmutable $generateUntil = null,
    ) {
        $this->recurrence ??= new EventRecurrenceData;
    }
}
```

Create `packages/events/src/Data/EventLocationData.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Events\Data;

use Capell\Events\Enums\EventLocationTypeEnum;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class EventLocationData extends Data
{
    public function __construct(
        public EventLocationTypeEnum $type = EventLocationTypeEnum::Physical,
        public ?string $name = null,
        public ?string $address = null,
        public ?string $url = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
    ) {
    }
}
```

Create `packages/events/src/Data/EventBookingData.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Events\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class EventBookingData extends Data
{
    public function __construct(
        public ?string $url = null,
        public ?string $label = null,
        public ?CarbonImmutable $opensAt = null,
        public ?CarbonImmutable $closesAt = null,
    ) {
    }
}
```

Create `packages/events/src/Data/EventCalendarDayData.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Events\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

class EventCalendarDayData extends Data
{
    public function __construct(
        public CarbonImmutable $date,
        public bool $isCurrentMonth,
        public bool $isToday,
        public bool $isSelected,
        public int $occurrenceCount,
    ) {
    }
}
```

Create `packages/events/src/Data/EventSchemaData.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Events\Data;

use Spatie\LaravelData\Data;

class EventSchemaData extends Data
{
    public function __construct(
        public ?string $organizer = null,
        public ?string $performer = null,
    ) {
    }
}
```

- [ ] **Step 3: Write model contract tests**

Create `packages/events/tests/Integration/Models/EventModelTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Core\Contracts\Pageable;
use Capell\Core\Contracts\PageCacheable;
use Capell\Core\Models\Contracts\Publishable;
use Capell\Core\Models\Contracts\Translatable;
use Capell\Core\Models\Contracts\Typeable;
use Capell\Core\Models\Contracts\Userstampable;
use Capell\Core\Models\Site;
use Capell\Events\Models\Event;
use Spatie\MediaLibrary\HasMedia;

it('implements Capell page contracts', function (): void {
    $event = new Event;

    expect($event)->toBeInstanceOf(Pageable::class)
        ->and($event)->toBeInstanceOf(PageCacheable::class)
        ->and($event)->toBeInstanceOf(Publishable::class)
        ->and($event)->toBeInstanceOf(Translatable::class)
        ->and($event)->toBeInstanceOf(Typeable::class)
        ->and($event)->toBeInstanceOf(Userstampable::class)
        ->and($event)->toBeInstanceOf(HasMedia::class);
});

it('has event occurrences', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $event = Event::factory()->site($site)->create();

    $occurrence = $event->occurrences()->create([
        'site_id' => $site->id,
        'starts_at' => '2026-06-01 09:00:00',
        'ends_at' => '2026-06-01 10:00:00',
        'timezone' => 'Europe/London',
        'status' => 'scheduled',
        'location' => [],
        'booking' => [],
        'schema' => [],
        'is_cancelled' => false,
    ]);

    expect($event->occurrences()->first()?->is($occurrence))->toBeTrue();
});
```

- [ ] **Step 4: Add migrations, models, and factories**

Create `create_events_table.php` mirroring Blog `create_articles_table.php`, with table name `events`.

Create `create_event_occurrences_table.php`:

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
        Schema::create('event_occurrences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->nullable()->index();
            $table->string('timezone')->default('UTC');
            $table->string('status')->default('scheduled')->index();
            $table->json('location')->nullable();
            $table->json('booking')->nullable();
            $table->json('schema')->nullable();
            $table->boolean('is_cancelled')->default(false)->index();
            $table->timestamps();
            $table->index(['site_id', 'starts_at']);
            $table->index(['event_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_occurrences');
    }
};
```

Create `Event` using Blog `Article` as the source pattern with table `events`, relation methods `occurrences()`, `nextOccurrence()`, `pageUrl()`, `pageUrls()`, `layout()`, `site()`, and media collection `image`.

Create `EventOccurrence` with fillable fields, casts for JSON/date/boolean fields, `event()` and `site()` relations, and scopes `upcoming()`, `between()`, `published()`, and `notCancelled()`.

- [ ] **Step 5: Run model and data tests**

Run:

```bash
vendor/bin/pest packages/events/tests/Unit/Data/EventDataTest.php packages/events/tests/Integration/Models/EventModelTest.php packages/events/tests/Integration/Models/EventOccurrenceModelTest.php
```

Expected: pass.

- [ ] **Step 6: Commit**

```bash
git add packages/events composer.json tests/Pest.php tests/Packages/PackagesTestCase.php
git commit -m "feat: add events data models"
```

---

## Task 3: Occurrence Generation Actions

**Files:**

- Create: `packages/events/src/Actions/GenerateEventOccurrencesAction.php`
- Create: `packages/events/src/Actions/DeleteFutureEventOccurrencesAction.php`
- Create: `packages/events/src/Actions/SyncEventOccurrencesAction.php`
- Test: occurrence action tests listed in File Structure.

- [ ] **Step 1: Write occurrence generation tests**

Create `packages/events/tests/Integration/Actions/GenerateEventOccurrencesActionTest.php` with tests for one-off, daily, weekly, and monthly schedules. The weekly test:

```php
<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\Events\Actions\GenerateEventOccurrencesAction;
use Capell\Events\Models\Event;

it('generates weekly occurrences on selected weekdays', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $event = Event::factory()->site($site)->create([
        'meta' => [
            'schedule' => [
                'starts_at' => '2026-06-01 09:00:00',
                'ends_at' => '2026-06-01 10:00:00',
                'timezone' => 'Europe/London',
                'generate_until' => '2026-06-15 23:59:59',
                'recurrence' => [
                    'frequency' => 'weekly',
                    'interval' => 1,
                    'weekdays' => ['monday', 'wednesday'],
                    'month_day' => null,
                    'until' => '2026-06-15',
                    'count' => null,
                ],
            ],
            'location' => ['type' => 'physical', 'name' => 'Town Hall'],
            'booking' => ['url' => 'https://example.test/book', 'label' => 'Book'],
        ],
    ]);

    GenerateEventOccurrencesAction::run($event);

    expect($event->occurrences()->orderBy('starts_at')->pluck('starts_at')->map->format('Y-m-d H:i:s')->all())
        ->toBe([
            '2026-06-01 09:00:00',
            '2026-06-03 09:00:00',
            '2026-06-08 09:00:00',
            '2026-06-10 09:00:00',
            '2026-06-15 09:00:00',
        ]);
});
```

- [ ] **Step 2: Implement generation action**

Create `GenerateEventOccurrencesAction` with `AsObject`, a single `handle(Event $event): Collection` method, and private methods for daily, weekly, and monthly date expansion. Use `CarbonImmutable`, preserve event duration, stop at the earliest of recurrence `until`, recurrence `count`, schedule `generateUntil`, or configured default generation window.

- [ ] **Step 3: Implement sync and delete actions**

`DeleteFutureEventOccurrencesAction::handle(Event $event, ?CarbonImmutable $from = null): int` deletes occurrence rows where `starts_at >= $from ?? now()`.

`SyncEventOccurrencesAction::handle(Event $event): Collection` calls delete future then generate.

- [ ] **Step 4: Run occurrence tests and commit**

Run:

```bash
vendor/bin/pest packages/events/tests/Integration/Actions/GenerateEventOccurrencesActionTest.php packages/events/tests/Integration/Actions/SyncEventOccurrencesActionTest.php
```

Expected: pass.

Commit:

```bash
git add packages/events
git commit -m "feat: generate event occurrences"
```

---

## Task 4: Package Registrar, Creator, Types, Layouts, And Admin Resource

**Files:**

- Create: `EventsModelRegistrar`, `EventsCreator`, loader, layout/type enums, resource classes, configurator classes.
- Modify: `EventsServiceProvider`, `AdminServiceProvider`.
- Test: `packages/events/tests/Feature/Filament/EventResourceTest.php`

- [ ] **Step 1: Write resource registration test**

Create `packages/events/tests/Feature/Filament/EventResourceTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Admin\Facades\CapellAdmin;
use Capell\Events\Enums\ResourceEnum;
use Capell\Events\Filament\Resources\Events\EventResource;
use Capell\Events\Providers\EventsServiceProvider;
use Capell\Core\Facades\CapellCore;

it('registers the event resource when the package is installed', function (): void {
    CapellCore::forcePackageInstalled(EventsServiceProvider::$packageName);

    expect(CapellAdmin::getResource('page', 'event'))->toBe(EventResource::class)
        ->and(ResourceEnum::Event->value)->toBe(EventResource::class);
});
```

- [ ] **Step 2: Implement registrar**

Create `EventsModelRegistrar` registering `Event::class`, a page variation named `event`, and a morph map alias `event`.

- [ ] **Step 3: Implement creator**

Create `EventsCreator` with methods:

- `createEventPageType()`
- `createEventsPageType()`
- `createCalendarPageType()`
- `createEventLayout()`
- `createEventsLayout()`
- `createCalendarLayout()`
- `createEventsPage(Site $site, ?Type $type = null, ?Layout $layout = null, ?Collection $languages = null): Page`
- `createCalendarPage(Page $parent, ?Type $type = null, ?Layout $layout = null, ?Collection $languages = null): Page`

Use `SetupPageUrlsAction::run($page)` after creating translations.

- [ ] **Step 4: Implement resource and configurator**

Create `EventResource` extending `Capell\Admin\Filament\Resources\Pages\PageResource` with model `Event::class`, slug `event`, form configurator `EventForm::class`, table configurator `EventPagesTable::class`, and pages `ListEvents`, `CreateEvent`, `EditEvent`.

Create `EventPageConfigurator` extending `DefaultPageConfigurator`; use `FixedWidthSidebar`, translation schema, settings schema, event schedule/location/booking sections, and `UrlsRelationManager`.

- [ ] **Step 5: Register providers**

In `EventsServiceProvider`, after package installation, call:

- `EventsModelRegistrar::register()`
- Blade component namespace registration
- Livewire page component registration
- `CapellCore::registerPageType(new PageTypeData(name: 'event', model: Event::class, label: fn (): string => __('capell-events::generic.event')))`

In `AdminServiceProvider`, register Event as a page resource, register `EventPageConfigurator`, and add default pages for Events and Calendar.

- [ ] **Step 6: Run resource tests and commit**

Run:

```bash
vendor/bin/pest packages/events/tests/Feature/Filament/EventResourceTest.php
```

Expected: pass.

Commit:

```bash
git add packages/events
git commit -m "feat: register events admin resource"
```

---

## Task 5: Calendar, Listings, Schema, And Feed Actions

**Files:**

- Create: `BuildEventCalendarMonthAction`, `BuildEventSchemaAction`, `BuildIcsFeedAction`, frontend views, Livewire page classes.
- Test: calendar, schema, feed action tests.

- [ ] **Step 1: Write action tests**

Calendar month test expects 42 day cells, selected date state, and occurrence counts for dates containing occurrences.

Schema test expects:

```php
expect($schema)
    ->toHaveKey('@context', 'https://schema.org')
    ->toHaveKey('@type', 'Event')
    ->toHaveKey('name')
    ->toHaveKey('startDate')
    ->toHaveKey('endDate')
    ->toHaveKey('eventAttendanceMode')
    ->toHaveKey('location');
```

Feed test expects output containing:

```text
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:event-
DTSTART:
DTEND:
SUMMARY:
END:VEVENT
END:VCALENDAR
```

- [ ] **Step 2: Implement calendar month action**

`BuildEventCalendarMonthAction::handle(Site $site, CarbonImmutable $month, ?CarbonImmutable $selectedDate = null): Collection` builds complete week rows from Monday to Sunday and returns `EventCalendarDayData` items with occurrence counts.

- [ ] **Step 3: Implement schema action**

`BuildEventSchemaAction::handle(Event $event, ?EventOccurrence $occurrence = null): array` returns schema.org Event JSON-LD with attendance mode derived from `EventLocationTypeEnum`.

- [ ] **Step 4: Implement iCal action**

`BuildIcsFeedAction::handle(Site $site, ?Language $language = null): string` queries upcoming, non-cancelled occurrences for the configured feed window and escapes text values for iCalendar output.

- [ ] **Step 5: Run action tests and commit**

Run:

```bash
vendor/bin/pest packages/events/tests/Integration/Actions/BuildEventCalendarMonthActionTest.php packages/events/tests/Integration/Actions/BuildEventSchemaActionTest.php packages/events/tests/Integration/Actions/BuildIcsFeedActionTest.php
```

Expected: pass.

Commit:

```bash
git add packages/events
git commit -m "feat: build event calendar feed and schema"
```

---

## Task 6: Frontend Pages And Routes

**Files:**

- Create: `packages/events/src/Livewire/Page/Events.php`
- Create: `packages/events/src/Livewire/Page/Calendar.php`
- Create: `packages/events/resources/views/livewire/page/events.blade.php`
- Create: `packages/events/resources/views/livewire/page/calendar.blade.php`
- Create: `packages/events/routes/web.php`
- Modify: `FrontendServiceProvider`
- Test: `packages/events/tests/Feature/EventsPageTest.php`
- Test: `packages/events/tests/Feature/CalendarPageTest.php`
- Test: `packages/events/tests/Feature/EventPageTest.php`
- Test: `packages/events/tests/Feature/EventFeedTest.php`

- [ ] **Step 1: Write frontend tests**

Events page test creates an Events page, an Event type/layout, two events with occurrences, then asserts the page shows events ordered by `starts_at`.

Calendar page test creates occurrences in the current month, visits the calendar page, and asserts day cells and event titles render.

Feed test visits the iCal route and asserts content type `text/calendar; charset=UTF-8` plus `BEGIN:VCALENDAR`.

- [ ] **Step 2: Implement Livewire Events page**

`Events` extends `Capell\Frontend\Livewire\Page\AbstractPage`, sets default view `capell-events::livewire.page.events`, queries upcoming occurrences for `Frontend::site()`, and exposes grouped results to the view.

- [ ] **Step 3: Implement Livewire Calendar page**

`Calendar` extends AbstractPage, stores `year`, `month`, and `selectedDate`, exposes `previousMonth()`, `nextMonth()`, `today()`, and `selectDate(string $date): void`, and refreshes the calendar using `BuildEventCalendarMonthAction`.

- [ ] **Step 4: Implement views**

The events view renders a `.events-list` wrapper, date group headings, event links, time, location, and booking link when available.

The calendar view renders a `.events-calendar` wrapper, month navigation buttons, stable day grid, occurrence counts, selected-date occurrence list, and a feed link.

- [ ] **Step 5: Add feed route**

Create `routes/web.php` with a named route returning `response(BuildIcsFeedAction::run($site, $language), 200, ['Content-Type' => 'text/calendar; charset=UTF-8'])`. Resolve site/language using existing frontend state or route parameters matching package conventions discovered in `packages/blog` and `packages/seo-tools`.

- [ ] **Step 6: Run frontend tests and commit**

Run:

```bash
vendor/bin/pest packages/events/tests/Feature/EventsPageTest.php packages/events/tests/Feature/CalendarPageTest.php packages/events/tests/Feature/EventPageTest.php packages/events/tests/Feature/EventFeedTest.php
```

Expected: pass.

Commit:

```bash
git add packages/events
git commit -m "feat: add events frontend pages"
```

---

## Task 7: Console Install, Setup, Docs, And Architecture Tests

**Files:**

- Create: `InstallPackageAction`, install/setup commands, arch tests, README updates, remaining language files.
- Modify: `ConsoleServiceProvider`, `README.md`.
- Test: package arch and command tests.

- [ ] **Step 1: Write command and arch tests**

Create `packages/events/tests/Arch/EventsPackageTest.php`:

```php
<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

it('keeps events package references inside the events source package', function (): void {
    $rootPath = dirname(__DIR__, 4);
    $violations = [];

    $files = (new Finder)
        ->files()
        ->in($rootPath . '/packages')
        ->path('/\/src\//')
        ->name('*.php')
        ->contains('Capell\\Events');

    foreach ($files as $file) {
        $relativePath = str_replace($rootPath . '/', '', $file->getPathname());

        if (str_starts_with($relativePath, 'packages/events/src/')) {
            continue;
        }

        $violations[] = $relativePath;
    }

    expect($violations)->toBeEmpty();
});

arch()
    ->expect('Capell\Events')
    ->classes()
    ->toUseStrictEquality();
```

- [ ] **Step 2: Implement install/setup commands**

`InstallCommand` registers package migrations/settings through the existing Capell install command pattern used by Blog and Mosaic.

`SetupCommand` resolves selected sites and runs `EventsCreator::setup($site)` to create types, layouts, default Events page, and Calendar child page.

- [ ] **Step 3: Fill translations**

Create language files for generic labels, form labels, table labels, navigation labels, and messages. Every user-facing string in PHP and Blade uses `__('capell-events::...')`.

- [ ] **Step 4: Update README**

Document installation order, setup command, event authoring fields, recurrence behavior, calendar page, feed route, schema output, and deferred scope.

- [ ] **Step 5: Run package tests and preflight**

Run:

```bash
vendor/bin/pest packages/events/tests
composer preflight
```

Expected: package tests pass and preflight completes cleanly.

- [ ] **Step 6: Commit**

```bash
git add packages/events composer.json tests/Pest.php tests/Packages/PackagesTestCase.php
git commit -m "feat: complete events package"
```

---

## Task 8: Final Verification

**Files:**

- Verify all events package files and root registration changes.

- [ ] **Step 1: Run focused package suite**

```bash
vendor/bin/pest packages/events/tests
```

Expected: all Events tests pass.

- [ ] **Step 2: Run wider package safety checks**

```bash
composer test
composer preflight
```

Expected: all tests and static checks pass.

- [ ] **Step 3: Review git diff**

```bash
git diff --stat HEAD
git diff --check
```

Expected: diff contains Events package files plus root registration changes only; `git diff --check` reports no whitespace errors.

- [ ] **Step 4: Final commit if needed**

If verification required fixes:

```bash
git add packages/events composer.json tests/Pest.php tests/Packages/PackagesTestCase.php
git commit -m "fix: verify events package"
```
