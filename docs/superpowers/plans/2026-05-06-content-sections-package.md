# Content Sections Package Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extract the existing LayoutBuilder `Section` model/table domain into an independent `capell-app/content-sections` package.

**Architecture:** Content Sections owns the `Section` model, `sections` table, Section Filament resource, configurators, asset registration, and rendering views. LayoutBuilder keeps widgets, widget assets, layouts, and layout UI, and consumes registered assets generically without importing `Capell\ContentSections`. Blog imports Content Sections only where it explicitly adds section tag relations.

**Tech Stack:** PHP 8.2, Laravel/Testbench, Pest, Filament 4/5 compatible resources, Spatie package tools, Capell Core/Admin/Frontend registries.

---

## File Structure

Create:

- `packages/content-sections/composer.json` - standalone Composer package metadata and PSR-4 autoload.
- `packages/content-sections/capell.json` - Capell marketplace/package manifest.
- `packages/content-sections/README.md` - short package overview.
- `packages/content-sections/config/capell-content-sections.php` - Content Sections asset config.
- `packages/content-sections/database/factories/SectionFactory.php` - moved Section factory under `Capell\ContentSections`.
- `packages/content-sections/database/migrations/create_sections_table.php` - moved existing `sections` migration.
- `packages/content-sections/resources/lang/en/*.php` - moved section-facing strings.
- `packages/content-sections/resources/views/components/section/*.blade.php` - moved section asset render components.
- `packages/content-sections/src/Providers/ContentSectionsServiceProvider.php` - package registration, model/type/asset/admin/frontend wiring.
- `packages/content-sections/src/Models/Section.php` - moved Section model backed by existing `sections` table.
- `packages/content-sections/src/Observers/SectionObserver.php` - moved observer.
- `packages/content-sections/src/Actions/CreateContentAction.php` - moved section creation action.
- `packages/content-sections/src/Actions/ModifyContentSelectCreateAction.php` - moved section create-option mutation.
- `packages/content-sections/src/Actions/MutateContentDataBeforeFillAction.php` - moved section default form-data mutation.
- `packages/content-sections/src/Actions/ReplicateContentAction.php` - moved section replication action.
- `packages/content-sections/src/Enums/AssetComponentEnum.php` - Section component enum.
- `packages/content-sections/src/Enums/AssetEnum.php` - Section asset enum.
- `packages/content-sections/src/Enums/ConfiguratorTypeEnum.php` - Section configurator group.
- `packages/content-sections/src/Enums/LayoutTypeEnum.php` - Section type enum value only.
- `packages/content-sections/src/Enums/LivewireComponentsEnum.php` - Section asset table registration.
- `packages/content-sections/src/Enums/ResourceEnum.php` - Section resource enum.
- `packages/content-sections/src/Enums/SectionConfiguratorEnum.php` - Section configurator enum.
- `packages/content-sections/src/Enums/TypeEnum.php` - Section type enum value only.
- `packages/content-sections/src/Filament/Configurators/Sections/*` - moved section configurators.
- `packages/content-sections/src/Filament/Components/Forms/Content/*` - moved section type/settings form components.
- `packages/content-sections/src/Filament/Resources/Sections/*` - moved Section resource, pages, tables, schemas, widgets, relation managers.
- `packages/content-sections/src/Livewire/Assets/Table/SectionAssets.php` - moved Section asset picker table.
- `packages/content-sections/src/Support/ContentSectionsModelRegistrar.php` - registers Section in Capell Core without LayoutBuilder.
- `packages/content-sections/tests/ContentSectionsTestCase.php` - package test case.
- `packages/content-sections/tests/Pest.php` - Pest bootstrap for package tests.
- `packages/content-sections/tests/Arch/ContentSectionsPackageTest.php` - boundary assertions.
- `packages/content-sections/tests/Integration/Providers/ContentSectionsServiceProviderTest.php` - provider/package registration.
- `packages/content-sections/tests/Integration/Model/SectionTest.php` - moved model behavior tests.
- `packages/content-sections/tests/Feature/Filament/Resources/Section/*` - moved resource tests.

Modify:

- `composer.local.json` - add ContentSections PSR-4 autoload and test autoload for the local workspace only; this file is ignored and must not be staged.
- `composer.json` - add ContentSections PSR-4 autoload and test autoload.
- `phpunit.xml` - include `packages/content-sections/tests`.
- `packages/layout-builder/src/Providers/LayoutBuilderServiceProvider.php` - remove Section imports/registration and keep only layout/widget ownership.
- `packages/layout-builder/src/Enums/AssetEnum.php` - delete when no cases remain, then remove imports that referenced it.
- `packages/layout-builder/src/Enums/TypeEnum.php` - remove Section case.
- `packages/layout-builder/src/Enums/LayoutTypeEnum.php` - remove Section case.
- `packages/layout-builder/src/Enums/ResourceEnum.php` - remove Section resource case.
- `packages/layout-builder/src/Enums/ConfiguratorTypeEnum.php` - remove Section configurator group.
- `packages/layout-builder/src/Enums/LivewireComponentsEnum.php` - remove Section asset table entry.
- `packages/layout-builder/src/Models/Widget.php` - remove hard `Section` relation type or resolve it by morph/registered asset.
- `packages/layout-builder/src/View/Components/Widget/Hero.php` - remove direct `Section` import and load asset morph relations through registered asset metadata.
- `packages/layout-builder/database/factories/WidgetAssetFactory.php` - avoid creating Section unless Content Sections exists; use page assets by default.
- `packages/layout-builder/src/Support/Creator/TypeCreator.php` - remove Section type creation.
- `packages/layout-builder/src/Support/Creator/ContentCreator.php` - move to Content Sections.
- `packages/layout-builder/src/Filament/Components/Forms/ContentSelect.php` - move to Content Sections.
- `packages/layout-builder/src/Filament/Components/Forms/Content/TypeSelect.php` - move to Content Sections.
- `packages/layout-builder/src/Filament/Components/Forms/Content/SettingsSchema.php` - move to Content Sections.
- `packages/layout-builder/src/Filament/Configurators/Types/ContentTypeConfigurator.php` - move to Content Sections.
- `packages/layout-builder/src/Filament/Configurators/Widgets/SectionWidgetAssetForm.php` - move to Content Sections.
- `packages/layout-builder/src/Filament/Resources/Pages/RelationManagers/SectionsRelationManager.php` - remove from LayoutBuilder.
- `packages/blog/composer.json` - require `capell-app/content-sections` if Blog keeps section tag relations.
- `packages/blog/capell.json` - add Content Sections to package requirements if Blog keeps section tag relations.
- `packages/blog/src/Providers/BlogServiceProvider.php` - import `Capell\ContentSections\Models\Section`.
- `packages/blog/tests/BlogTestCase.php` - register Content Sections provider and force-install package.
- `tests/AbstractTestCase.php` - morph map `section` to `Capell\ContentSections\Models\Section`.
- `tests/UninstalledPackages/Integration/UninstalledPackageServiceProviderTest.php` - assert Content Sections is uninstalled separately from LayoutBuilder.
- `tests/Packages/Integration/Model/TagTest.php` - import `Capell\ContentSections\Models\Section`.
- `tests/Packages/Integration/Model/ContentTest.php` - import `Capell\ContentSections\Models\Section`.
- `packages/layout-builder/tests/*` - move Section tests to Content Sections, update remaining LayoutBuilder tests to use generic registered asset data or page assets.

Delete or move:

- `packages/layout-builder/src/Models/Section.php`
- `packages/layout-builder/src/Observers/SectionObserver.php`
- `packages/layout-builder/database/factories/SectionFactory.php`
- `packages/layout-builder/database/migrations/create_sections_table.php`
- `packages/layout-builder/src/Filament/Resources/Sections/`
- `packages/layout-builder/src/Filament/Configurators/Sections/`
- `packages/layout-builder/src/Livewire/Assets/Table/SectionAssets.php`
- `packages/layout-builder/resources/views/components/section/`

## Task 1: Package Scaffold And Test Harness

**Files:**

- Create: `packages/content-sections/composer.json`
- Create: `packages/content-sections/capell.json`
- Create: `packages/content-sections/README.md`
- Create: `packages/content-sections/config/capell-content-sections.php`
- Create: `packages/content-sections/tests/ContentSectionsTestCase.php`
- Create: `packages/content-sections/tests/Pest.php`
- Modify locally, do not stage: `composer.local.json`
- Modify: `composer.json`
- Modify: `phpunit.xml`

- [ ] **Step 1: Add a failing provider discovery test**

Create `packages/content-sections/tests/Integration/Providers/ContentSectionsServiceProviderTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\ContentSections\Providers\ContentSectionsServiceProvider;
use Capell\Core\Facades\CapellCore;

it('registers content sections as a standalone Capell package', function (): void {
    expect(CapellCore::hasPackage(ContentSectionsServiceProvider::$packageName))->toBeTrue()
        ->and(CapellCore::getPackage(ContentSectionsServiceProvider::$packageName)->serviceProviderClass)
        ->toBe(ContentSectionsServiceProvider::class);
});
```

- [ ] **Step 2: Run the failing test**

Run: `vendor/bin/pest packages/content-sections/tests/Integration/Providers/ContentSectionsServiceProviderTest.php --colors=always`

Expected: FAIL because `Capell\ContentSections\Providers\ContentSectionsServiceProvider` does not exist.

- [ ] **Step 3: Create scaffold files**

`packages/content-sections/composer.json`:

```json
{
    "name": "capell-app/content-sections",
    "description": "Reusable content sections for Capell",
    "keywords": ["capell", "content-sections", "laravel", "filamentphp", "cms"],
    "homepage": "https://github.com/capell-app/content-sections",
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
        "capell-app/core": "*",
        "capell-app/frontend": "*"
    },
    "suggest": {
        "capell-app/layout-builder": "Enables LayoutBuilder asset selection for content sections.",
        "capell-app/publishing-studio": "Enables workspace-aware section publishing integration."
    },
    "autoload": {
        "psr-4": {
            "Capell\\ContentSections\\": "src/",
            "Capell\\ContentSections\\Database\\Factories\\": "database/factories"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Capell\\ContentSections\\Providers\\ContentSectionsServiceProvider"
            ]
        }
    },
    "config": {
        "sort-packages": true
    },
    "prefer-stable": true
}
```

`packages/content-sections/capell.json`:

```json
{
    "name": "capell-app/content-sections",
    "kind": "package",
    "capell-version": "^4.0",
    "productGroup": "Capell Foundation",
    "tier": "free",
    "bundle": "foundation",
    "contexts": ["admin", "frontend"],
    "requires": ["capell-app/core", "capell-app/admin", "capell-app/frontend"],
    "providers": {
        "shared": [
            "Capell\\ContentSections\\Providers\\ContentSectionsServiceProvider"
        ]
    }
}
```

`packages/content-sections/config/capell-content-sections.php`:

```php
<?php

declare(strict_types=1);

use Capell\ContentSections\Models\Section;
use Filament\Support\Icons\Heroicon;

return [
    'assets' => [
        'section' => [
            'color' => 'info',
            'icon' => Heroicon::OutlinedClipboardDocumentList,
            'model' => Section::class,
        ],
    ],
];
```

`packages/content-sections/tests/Pest.php`:

```php
<?php

declare(strict_types=1);

use Capell\ContentSections\Tests\ContentSectionsTestCase;

pest()->extend(ContentSectionsTestCase::class)->group('content-sections')->in(__DIR__);
```

- [ ] **Step 4: Add a minimal service provider and test case**

Create `packages/content-sections/src/Providers/ContentSectionsServiceProvider.php` with package metadata registration only:

```php
<?php

declare(strict_types=1);

namespace Capell\ContentSections\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Composer\InstalledVersions;
use Spatie\LaravelPackageTools\Package;

class ContentSectionsServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-content-sections';

    public static string $packageName = 'capell-app/content-sections';

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name)
            ->hasConfigFile()
            ->hasViews(self::$name)
            ->hasTranslations();
    }

    public function registeringPackage(): void
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            version: $this->getVersion(),
            description: fn (): string => __('capell-content-sections::package.description'),
        );
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

Create `packages/content-sections/tests/ContentSectionsTestCase.php` with this shape:

```php
<?php

declare(strict_types=1);

namespace Capell\ContentSections\Tests;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Providers\Filament\AdminPanelProvider;
use Capell\ContentSections\Providers\ContentSectionsServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Media;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Tests\AbstractTestCase;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Livewire\LivewireServiceProvider;
use Override;
use Spatie\ImageOptimizer\Optimizers\Svgo;

class ContentSectionsTestCase extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Blade::anonymousComponentPath(__DIR__ . '/../../foundation-theme/resources/views/components', 'capell');

        $this->registerAndMigrateSettings(
            CapellCore::getSettingMigrations(),
            __DIR__ . '/../../../vendor/capell-app/core/database/settings',
        );

        $this->registerAndMigrateSettings(
            CapellAdmin::getSettingMigrations(),
            __DIR__ . '/../../../vendor/capell-app/admin/database/settings',
        );
    }

    protected function getPackageServiceName(): string
    {
        return 'capell-content-sections';
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            ContentSectionsServiceProvider::class,
            AdminPanelProvider::class,
            AdminServiceProvider::class,
            FrontendServiceProvider::class,
            FoundationThemeServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(FrontendServiceProvider::$packageName);
        CapellCore::forcePackageInstalled('capell-app/foundation-theme');
        CapellCore::forcePackageInstalled(ContentSectionsServiceProvider::$packageName);

        $app->make(Repository::class)->set('media-library.media_model', Media::class);
        $app->make(Repository::class)->set('media-library.image_optimizers', [
            Svgo::class => [],
        ]);
    }
}
```

- [ ] **Step 5: Add autoload and suite entries**

Add to `composer.json` PSR-4 autoload:

```json
"Capell\\ContentSections\\": "packages/content-sections/src",
"Capell\\ContentSections\\Database\\Factories\\": "packages/content-sections/database/factories"
```

Add to `composer.json` autoload-dev:

```json
"Capell\\ContentSections\\Tests\\": "packages/content-sections/tests"
```

Also add the same entries to local `composer.local.json` so this workspace can run focused tests. Because `.gitignore` excludes `composer.local.*`, verify the entries exist locally but do not stage or commit `composer.local.json`.

Add `packages/content-sections/tests` to `phpunit.xml` beside other package test suites.

- [ ] **Step 6: Dump autoload and verify the scaffold**

Run: `COMPOSER=composer.local.json composer dump-autoload --no-scripts`

Run: `vendor/bin/pest packages/content-sections/tests/Integration/Providers/ContentSectionsServiceProviderTest.php --colors=always`

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add packages/content-sections composer.json phpunit.xml
git commit -m "feat: scaffold content sections package"
```

## Task 2: Move Section Model, Migration, Factory, And Core Registration

**Files:**

- Move: `packages/layout-builder/src/Models/Section.php` to `packages/content-sections/src/Models/Section.php`
- Move: `packages/layout-builder/src/Observers/SectionObserver.php` to `packages/content-sections/src/Observers/SectionObserver.php`
- Move: `packages/layout-builder/database/factories/SectionFactory.php` to `packages/content-sections/database/factories/SectionFactory.php`
- Move: `packages/layout-builder/database/migrations/create_sections_table.php` to `packages/content-sections/database/migrations/create_sections_table.php`
- Create: `packages/content-sections/database/factories/ContentTypeFactory.php`
- Create: `packages/content-sections/src/Enums/LayoutTypeEnum.php`
- Create: `packages/content-sections/src/Support/ContentSectionsModelRegistrar.php`
- Modify: `packages/content-sections/src/Providers/ContentSectionsServiceProvider.php`
- Modify: `tests/AbstractTestCase.php`
- Modify: `packages/layout-builder/src/Support/LayoutModelRegistrar.php`
- Modify: `packages/layout-builder/src/Providers/LayoutBuilderServiceProvider.php`

- [ ] **Step 1: Add failing model and morph tests**

Create `packages/content-sections/tests/Integration/Model/SectionTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\ContentSections\Models\Section;
use Illuminate\Database\Eloquent\Relations\Relation;

it('uses the existing sections table and section morph alias', function (): void {
    $section = Section::factory()->create(['name' => 'Feature strip']);

    expect($section->getTable())->toBe('sections')
        ->and($section->getMorphClass())->toBe('section')
        ->and(Relation::getMorphedModel('section'))->toBe(Section::class);
});
```

- [ ] **Step 2: Run the failing test**

Run: `vendor/bin/pest packages/content-sections/tests/Integration/Model/SectionTest.php --colors=always`

Expected: FAIL because `Capell\ContentSections\Models\Section` does not exist.

- [ ] **Step 3: Move files and update namespaces**

Move the four Section files listed above. Update namespace/imports:

```php
namespace Capell\ContentSections\Models;

use Capell\ContentSections\Database\Factories\SectionFactory;
use Capell\ContentSections\Models\Concerns\ComposhipsJsonRelationshipsTrait;
use Capell\ContentSections\Observers\SectionObserver;
```

Move `packages/layout-builder/src/Models/Concerns/ComposhipsJsonRelationshipsTrait.php` to `packages/content-sections/src/Models/Concerns/ComposhipsJsonRelationshipsTrait.php` when the Section model still uses it.

Remove `use Capell\LayoutBuilder\Models\Widget;` and `use Capell\LayoutBuilder\Models\WidgetAsset;` from the moved `Section` model. Remove the `widgetAssets()`, `pages()`, and `widgets()` methods from `Section`; those relations are LayoutBuilder placement concerns, not Content Sections ownership.

Create `packages/content-sections/database/factories/ContentTypeFactory.php` so the moved `SectionFactory` does not import LayoutBuilder:

```php
<?php

declare(strict_types=1);

namespace Capell\ContentSections\Database\Factories;

use Capell\ContentSections\Enums\LayoutTypeEnum;
use Capell\Core\Database\Factories\TypeFactory;

class ContentTypeFactory extends TypeFactory
{
    public function definition(): array
    {
        return [
            ...parent::definition(),
            'type' => LayoutTypeEnum::Section->value,
        ];
    }
}
```

Update the moved `SectionFactory` namespace/imports:

```php
namespace Capell\ContentSections\Database\Factories;

use Capell\ContentSections\Models\Section;
```

Keep `'type_id' => (new ContentTypeFactory),`.

- [ ] **Step 4: Move observer default type lookup into Content Sections**

Create the minimal enum required by the observer at `packages/content-sections/src/Enums/LayoutTypeEnum.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\ContentSections\Enums;

use Capell\ContentSections\Models\Section;
use Filament\Support\Contracts\HasLabel;

enum LayoutTypeEnum: string implements HasLabel
{
    case Section = 'section';

    public function getModel(): string
    {
        return match ($this) {
            self::Section => Section::class,
        };
    }

    public function getTable(): string
    {
        return match ($this) {
            self::Section => 'sections',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Section => 'Section',
        };
    }
}
```

Update `SectionObserver` imports:

```php
use Capell\ContentSections\Enums\LayoutTypeEnum;
use Capell\ContentSections\Models\Section;
```

The default type lookup stays:

```php
$section->type_id = Type::query()->where('type', LayoutTypeEnum::Section)->default()->value('id');
```

- [ ] **Step 5: Register model and morph alias in Content Sections**

Create `packages/content-sections/src/Support/ContentSectionsModelRegistrar.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\ContentSections\Support;

use Capell\ContentSections\Models\Section;
use Capell\Core\Facades\CapellCore;
use Illuminate\Database\Eloquent\Relations\Relation;

class ContentSectionsModelRegistrar
{
    public static function register(): void
    {
        CapellCore::registerModel(Section::class);

        Relation::morphMap([
            'section' => Section::class,
        ], merge: true);
    }
}
```

Call it from `ContentSectionsServiceProvider::bootInstalledPackage()`.

Update `registeringPackage()` so installed package boot happens after Capell package metadata is registered:

```php
public function registeringPackage(): void
{
    CapellCore::registerPackage(
        static::$packageName,
        type: static::getType(),
        serviceProviderClass: static::class,
        path: realpath(__DIR__ . '/../..'),
        version: $this->getVersion(),
        description: fn (): string => __('capell-content-sections::package.description'),
    );

    $this->app->booted(function (): void {
        if (! $this->isPackageInstalled()) {
            return;
        }

        $this->bootInstalledPackage();
    });
}
```

Add `bootInstalledPackage()` to `ContentSectionsServiceProvider`:

```php
private function isPackageInstalled(): bool
{
    return CapellCore::getPackage(static::$packageName)->isInstalled();
}

private function bootInstalledPackage(): self
{
    return $this
        ->registerModels();
}

private function registerModels(): self
{
    ContentSectionsModelRegistrar::register();

    return $this;
}
```

- [ ] **Step 6: Remove Section from LayoutBuilder model registration**

Update `packages/layout-builder/src/Support/LayoutModelRegistrar.php` so it registers only:

```php
Widget::class,
WidgetAsset::class,
```

Remove `use Capell\LayoutBuilder\Models\Section;`.

- [ ] **Step 7: Update shared test morph map**

Change `tests/AbstractTestCase.php` import to:

```php
use Capell\ContentSections\Models\Section;
```

Keep the morph map key:

```php
'section' => Section::class,
```

- [ ] **Step 8: Run package model test**

Run: `vendor/bin/pest packages/content-sections/tests/Integration/Model/SectionTest.php --colors=always`

Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add packages/content-sections packages/layout-builder/src/Support/LayoutModelRegistrar.php packages/layout-builder/src/Providers/LayoutBuilderServiceProvider.php tests/AbstractTestCase.php
git add -u packages/layout-builder/src/Models/Section.php packages/layout-builder/src/Observers/SectionObserver.php packages/layout-builder/database/factories/SectionFactory.php packages/layout-builder/database/migrations/create_sections_table.php
git commit -m "feat: move section model into content sections"
```

## Task 3: Move Section Actions, Enums, Configurators, Resource, Livewire Table, Views, And Translations

**Files:**

- Move: Section-specific actions, enums, Filament resource files, configurators, Livewire table, Blade section components, translations.
- Modify: `packages/content-sections/src/Providers/ContentSectionsServiceProvider.php`
- Modify: LayoutBuilder enums/resources to remove Section cases.

- [ ] **Step 1: Move resource tests first**

Move the Section resource tests from:

```text
packages/layout-builder/tests/Feature/Filament/Resources/Section/
```

to:

```text
packages/content-sections/tests/Feature/Filament/Resources/Section/
```

Replace imports:

```php
Capell\LayoutBuilder\Filament\Resources\Sections\
Capell\LayoutBuilder\Models\Section
```

with:

```php
Capell\ContentSections\Filament\Resources\Sections\
Capell\ContentSections\Models\Section
```

- [ ] **Step 2: Run a moved resource test and confirm failure**

Run: `vendor/bin/pest packages/content-sections/tests/Feature/Filament/Resources/Section/SectionResourceTest.php --colors=always`

Expected: FAIL because the resource namespace is not moved yet.

- [ ] **Step 3: Move Section code surface**

Move these directories and files:

```text
packages/layout-builder/src/Filament/Resources/Sections -> packages/content-sections/src/Filament/Resources/Sections
packages/layout-builder/src/Filament/Configurators/Sections -> packages/content-sections/src/Filament/Configurators/Sections
packages/layout-builder/src/Livewire/Assets/Table/SectionAssets.php -> packages/content-sections/src/Livewire/Assets/Table/SectionAssets.php
packages/layout-builder/src/Enums/SectionConfiguratorEnum.php -> packages/content-sections/src/Enums/SectionConfiguratorEnum.php
packages/layout-builder/resources/views/components/section -> packages/content-sections/resources/views/components/section
```

Move section-only actions:

```text
packages/layout-builder/src/Actions/CreateContentAction.php
packages/layout-builder/src/Actions/ModifyContentSelectCreateAction.php
packages/layout-builder/src/Actions/MutateContentDataBeforeFillAction.php
packages/layout-builder/src/Actions/ReplicateContentAction.php
```

Move section-only form components:

```text
packages/layout-builder/src/Filament/Components/Forms/Content/TypeSelect.php
packages/layout-builder/src/Filament/Components/Forms/Content/SettingsSchema.php
packages/layout-builder/src/Filament/Components/Forms/ContentSelect.php
packages/layout-builder/src/Filament/Configurators/Types/ContentTypeConfigurator.php
```

Do not move these LayoutBuilder-backed Section relation managers into Content Sections:

```text
packages/layout-builder/src/Filament/Resources/Sections/RelationManagers/PagesRelationManager.php
packages/layout-builder/src/Filament/Resources/Sections/RelationManagers/WidgetsRelationManager.php
```

Delete those files from LayoutBuilder after removing them from the Section resource. Content Sections stays independent by exposing Section assets through Core `AssetRelation`; page/widget usage can be reintroduced later through a generic admin-surface extension point.

- [ ] **Step 4: Create Content Sections enums**

Use Content Sections namespace and package translations/config. The Section asset enum should keep value `section`:

```php
enum AssetEnum: string implements HasColor, HasIcon, HasLabel
{
    case Section = 'section';
}
```

`AssetEnum::getModel()` must return:

```php
config('capell-content-sections.assets.section.model', Section::class)
```

Blade component must be:

```php
AssetComponentEnum::Section->value
```

where `AssetComponentEnum::Section` is:

```php
case Section = 'capell-content-sections::section.asset';
```

- [ ] **Step 5: Register resources, configurators, assets, Livewire component, views, and publishing**

Extend `ContentSectionsServiceProvider::bootInstalledPackage()` with:

```php
return $this
    ->registerModels()
    ->registerRelationships()
    ->registerResources()
    ->registerConfigurators()
    ->registerTypes()
    ->registerAssets()
    ->registerEvents()
    ->registerLivewireComponents()
    ->registerBladeComponents()
    ->registerBlazeComponents()
    ->registerPublishingStudio();
```

Register Section resource with:

```php
CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
    class: ResourceEnum::Section->value,
    group: ResourceEnum::Section->name,
));
```

Register Core/Admin/Frontend assets with `AssetEnum::Section` and the same `AssetData`, `AdminAssetData`, and `FrontendAssetData` pattern currently in LayoutBuilder.

Move `packages/layout-builder/src/Filament/Concerns/HasAssetsRelationManager.php` to `packages/content-sections/src/Filament/Concerns/HasAssetsRelationManager.php` because the Section resource uses Core asset relations. Update translations in that trait from `capell-layout-builder::...` to `capell-content-sections::...` or shared `capell-admin::...` keys.

- [ ] **Step 6: Register Section relationships without LayoutBuilder imports**

In Content Sections provider register only Core relationships:

```php
Site::resolveRelationUsing(
    'sections',
    fn (Site $model): HasMany => $model->hasMany(Section::class, 'site_id'),
);

Type::resolveRelationUsing(
    'sections',
    fn (Type $model): HasMany => $model->hasMany(Section::class, 'type_id'),
);
```

Do not import `Capell\LayoutBuilder\Models\Widget` or `WidgetAsset` in Content Sections.

Update `SectionResource::getRelations()` to return only:

```php
return [
    SectionAssetsRelationManager::class,
];
```

- [ ] **Step 7: Remove Section ownership from LayoutBuilder enums/provider**

Remove Section cases and imports from:

```text
packages/layout-builder/src/Enums/AssetEnum.php
packages/layout-builder/src/Enums/TypeEnum.php
packages/layout-builder/src/Enums/LayoutTypeEnum.php
packages/layout-builder/src/Enums/ResourceEnum.php
packages/layout-builder/src/Enums/ConfiguratorTypeEnum.php
packages/layout-builder/src/Enums/LivewireComponentsEnum.php
packages/layout-builder/src/Providers/LayoutBuilderServiceProvider.php
```

`LayoutBuilderServiceProvider::registerResources()` must register Widget and Layout only.

`LayoutBuilderServiceProvider::registerTypes()` must register Widget only.

`LayoutBuilderServiceProvider::registerAssets()` must not register Section.

Delete `packages/layout-builder/src/Enums/AssetEnum.php` because Section is its only case. Update all references that used it. Widget definitions that previously stored `section` in `admin.asset_types` must store the string `'section'` directly; LayoutBuilder is allowed to support a configured asset type string without owning that asset.

- [ ] **Step 8: Run moved resource tests**

Run: `vendor/bin/pest packages/content-sections/tests/Feature/Filament/Resources/Section packages/content-sections/tests/Integration/Providers/ContentSectionsServiceProviderTest.php --colors=always`

Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add packages/content-sections packages/layout-builder/src packages/layout-builder/resources
git add -u packages/layout-builder/src/Filament/Resources/Sections packages/layout-builder/src/Filament/Configurators/Sections packages/layout-builder/src/Livewire/Assets/Table/SectionAssets.php packages/layout-builder/resources/views/components/section
git commit -m "feat: move section admin surface into content sections"
```

## Task 4: Make LayoutBuilder Consume Assets Generically

**Files:**

- Modify: `packages/layout-builder/src/Livewire/Filament/ManagesAssets.php`
- Modify: `packages/layout-builder/src/Filament/Concerns/HasAssetsRelationManager.php`
- Modify: `packages/layout-builder/src/Filament/Resources/Widgets/Schemas/WidgetAssetForm.php`
- Modify: `packages/layout-builder/src/Filament/Resources/Widgets/Tables/WidgetAssetsTable.php`
- Modify: `packages/layout-builder/src/View/Components/Widget/Hero.php`
- Modify: `packages/layout-builder/database/factories/WidgetAssetFactory.php`
- Modify: LayoutBuilder tests that assumed Section was built in.

- [ ] **Step 1: Add LayoutBuilder no-ContentSections arch test**

Add to `packages/layout-builder/tests/Arch/LayoutPackageTest.php`:

```php
arch('layout builder does not import content sections')
    ->expect('Capell\LayoutBuilder')
    ->not->toUse('Capell\ContentSections');
```

- [ ] **Step 2: Run the arch test**

Run: `vendor/bin/pest packages/layout-builder/tests/Arch/LayoutPackageTest.php --colors=always`

Expected: FAIL until direct Section assumptions are removed or moved.

- [ ] **Step 3: Replace Section hard-coding with Core/Admin/Frontend asset registry lookups**

Where LayoutBuilder currently branches on `LayoutTypeEnum::Section`, replace with asset metadata from:

```php
CapellCore::getAssets()
CapellCore::getAsset($assetType)
CapellAdmin::getAsset($assetType)
resolve(AssetsRegistryInterface::class)
```

The code must accept asset type strings, including `page` and `section`, without importing the model class for optional assets.

- [ ] **Step 4: Keep WidgetAssetFactory independent**

Change default `WidgetAssetFactory` asset creation to Page assets. Use `Section` only in tests under Content Sections or in integration tests that explicitly register Content Sections.

Default state:

```php
'asset_type' => 'page',
'asset_id' => fn (): string => (string) Page::factory()->create()->getKey(),
```

Do not import `Capell\ContentSections\Models\Section` in LayoutBuilder tests or factories.

- [ ] **Step 5: Update remaining LayoutBuilder tests**

LayoutBuilder-only tests should use Page assets or assert generic asset behavior. Integration tests that assert Section assets must move to `packages/content-sections/tests` and register both providers.

- [ ] **Step 6: Run LayoutBuilder focused tests**

Run: `vendor/bin/pest packages/layout-builder/tests/Arch/LayoutPackageTest.php packages/layout-builder/tests/Integration/Actions/InstallPackageActionTest.php packages/layout-builder/tests/Integration/Model/WidgetAssetTest.php --colors=always`

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add packages/layout-builder
git commit -m "refactor: make layout builder section agnostic"
```

## Task 5: Update Blog And Shared Cross-Package Tests

**Files:**

- Modify: `packages/blog/composer.json`
- Modify: `packages/blog/capell.json`
- Modify: `packages/blog/src/Providers/BlogServiceProvider.php`
- Modify: `packages/blog/tests/BlogTestCase.php`
- Modify: `tests/Packages/Integration/Model/TagTest.php`
- Modify: `tests/Packages/Integration/Model/ContentTest.php`
- Modify: `tests/UninstalledPackages/Integration/UninstalledPackageServiceProviderTest.php`

- [ ] **Step 1: Update Blog dependency metadata**

Add to `packages/blog/composer.json` require:

```json
"capell-app/content-sections": "*"
```

Add to `packages/blog/capell.json` requires:

```json
"capell-app/content-sections"
```

- [ ] **Step 2: Update Blog Section imports**

In `packages/blog/src/Providers/BlogServiceProvider.php`, replace:

```php
use Capell\LayoutBuilder\Models\Section;
```

with:

```php
use Capell\ContentSections\Models\Section;
```

Keep the existing `class_exists(Section::class)` guard around optional relation registration.

- [ ] **Step 3: Register Content Sections in Blog tests**

In `packages/blog/tests/BlogTestCase.php`, add:

```php
use Capell\ContentSections\Providers\ContentSectionsServiceProvider;
```

Add provider before Blog provider:

```php
ContentSectionsServiceProvider::class,
```

Force install:

```php
CapellCore::forcePackageInstalled(ContentSectionsServiceProvider::$packageName);
```

- [ ] **Step 4: Update shared tests**

Replace Section imports in shared tests:

```php
use Capell\ContentSections\Models\Section;
```

In uninstalled package tests, add `ContentSectionsServiceProvider::$packageName` to expected package registration and assert `Section::class` is not registered when uninstalled.

- [ ] **Step 5: Run Blog and shared focused tests**

Run: `vendor/bin/pest packages/blog/tests/Unit/ManifestRequirementsTest.php packages/blog/tests/Integration/Models/ArticleTest.php tests/Packages/Integration/Model/TagTest.php tests/Packages/Integration/Model/ContentTest.php tests/UninstalledPackages/Integration/UninstalledPackageServiceProviderTest.php --colors=always`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/blog tests/Packages/Integration/Model/TagTest.php tests/Packages/Integration/Model/ContentTest.php tests/UninstalledPackages/Integration/UninstalledPackageServiceProviderTest.php
git commit -m "chore: consume content sections from blog"
```

## Task 6: Boundary Verification, Autoload, And Final Cleanup

**Files:**

- Modify: docs and package readmes that still describe LayoutBuilder as owning Sections.
- Verify: all files touched by prior tasks.

- [ ] **Step 1: Search for old Section namespace**

Run: `rg -n "Capell\\\\LayoutBuilder\\\\Models\\\\Section|LayoutBuilder\\\\\\\\Filament\\\\\\\\Resources\\\\\\\\Sections|LayoutBuilder\\\\\\\\Filament\\\\\\\\Configurators\\\\\\\\Sections" packages tests composer.json composer.local.json phpunit.xml`

Expected: no code matches. Update docs that still describe LayoutBuilder as owning Sections.

- [ ] **Step 2: Search for forbidden cross-package imports**

Run: `rg -n "Capell\\\\LayoutBuilder" packages/content-sections`

Expected: no matches.

Run: `rg -n "Capell\\\\ContentSections" packages/layout-builder`

Expected: no matches.

- [ ] **Step 3: Run focused package tests**

Run: `vendor/bin/pest packages/content-sections/tests packages/layout-builder/tests/Arch/LayoutPackageTest.php packages/blog/tests/Unit/ManifestRequirementsTest.php --colors=always`

Expected: PASS.

- [ ] **Step 4: Run lint on changed PHP files**

Run: `composer lint:changed`

Expected: PASS.

- [ ] **Step 5: Run package preflight if focused tests pass**

Run: `composer preflight`

Expected: PASS.

- [ ] **Step 6: Final commit**

When cleanup changes are needed:

```bash
git add packages/content-sections packages/layout-builder packages/blog tests composer.json composer.local.json phpunit.xml
git commit -m "test: verify content sections extraction"
```

When there are no cleanup changes, do not create an empty commit.
