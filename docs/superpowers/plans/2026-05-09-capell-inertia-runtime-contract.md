# Capell Inertia Runtime Contract Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add the first runtime-aware Capell frontend contract so Blade, Livewire, and Inertia can be selected and validated through shared Core/Frontend primitives.

**Architecture:** Core owns runtime vocabulary and theme/package metadata. Core also exposes small presentation registry contracts that do not import frontend runtime implementations. Frontend owns response renderer selection and keeps current Livewire behaviour as the default renderer for backwards compatibility.

**Tech Stack:** PHP 8.2, Laravel, Pest, Spatie Laravel Data, Capell Core, Capell Frontend.

---

## Scope

This plan implements the first executable slice of [the Inertia runtime design](../specs/2026-05-09-capell-inertia-runtime-design.md):

- `FrontendRuntime` enum.
- Manifest `runtime` and `frontend` metadata.
- Theme definition runtime metadata.
- Core presentation registry contracts.
- Frontend response renderer contract and registry.
- Livewire renderer adapter preserving existing behaviour.

It does not build the `capell-app/inertia-runtime` package or an Inertia theme. Those should be separate plans after this contract lands.

## File Structure

Core files in `/Users/ben/Sites/packages/capell/capell-4`:

- Create `packages/core/src/Enums/FrontendRuntime.php` for the shared runtime vocabulary.
- Modify `packages/core/src/Support/Manifest/CapellManifestData.php` to hydrate and serialize runtime/frontend manifest metadata.
- Modify `packages/core/src/Support/Manifest/ManifestValidator.php` to validate runtime values and frontend metadata shape.
- Modify `packages/core/src/ThemeStudio/Data/ThemeDefinitionData.php` to carry runtime metadata with a backwards-compatible default.
- Create `packages/core/src/ThemeStudio/Contracts/PagePresentation.php` for page-type runtime presentation contracts.
- Create `packages/core/src/ThemeStudio/Contracts/WidgetPresentation.php` for widget runtime presentation contracts.
- Create `packages/core/src/ThemeStudio/Theme/PagePresentationRegistry.php` for page presentation lookup.
- Create `packages/core/src/ThemeStudio/Theme/WidgetPresentationRegistry.php` for widget presentation lookup.
- Modify `packages/core/src/Providers/CapellServiceProvider.php` to bind the new registries.
- Modify tests under `packages/core/tests/Unit/Manifest` and `packages/core/tests/Unit/ThemeStudio`.

Frontend files in `/Users/ben/Sites/packages/capell/capell-4`:

- Create `packages/frontend/src/Contracts/FrontendResponseRenderer.php`.
- Create `packages/frontend/src/Support/Render/FrontendResponseRendererRegistry.php`.
- Create `packages/frontend/src/Support/Render/LivewireFrontendResponseRenderer.php`.
- Modify `packages/frontend/src/Http/Controllers/PageController.php` to delegate page/error rendering to the registry.
- Modify `packages/frontend/src/Providers/FrontendServiceProvider.php` to bind the registry and register the Livewire renderer.
- Add tests under `packages/frontend/tests/Unit/Render` and update `packages/frontend/tests/Feature` coverage for controller behaviour.

Plan file in `/Users/ben/Sites/packages/capell/capell-packages-4`:

- This plan: `docs/superpowers/plans/2026-05-09-capell-inertia-runtime-contract.md`.

## Task 1: Core Runtime Manifest Metadata

**Files:**
- Create: `/Users/ben/Sites/packages/capell/capell-4/packages/core/src/Enums/FrontendRuntime.php`
- Modify: `/Users/ben/Sites/packages/capell/capell-4/packages/core/src/Support/Manifest/CapellManifestData.php`
- Modify: `/Users/ben/Sites/packages/capell/capell-4/packages/core/src/Support/Manifest/ManifestValidator.php`
- Modify: `/Users/ben/Sites/packages/capell/capell-4/packages/core/tests/Unit/Manifest/CapellManifestDataTest.php`
- Modify: `/Users/ben/Sites/packages/capell/capell-4/packages/core/tests/Unit/Manifest/ManifestValidatorTest.php`

- [ ] **Step 1: Write failing manifest data tests**

Append these tests to `/Users/ben/Sites/packages/capell/capell-4/packages/core/tests/Unit/Manifest/CapellManifestDataTest.php`:

```php
it('hydrates runtime and frontend metadata for theme manifests', function (): void {
    $manifest = CapellManifestData::fromArray([
        'manifest-version' => 2,
        'name' => 'capell-app/theme-inertia-corporate',
        'kind' => 'theme',
        'capell-version' => '^4.0',
        'themeKey' => 'inertia-corporate',
        'runtime' => 'inertia',
        'frontend' => [
            'entry' => 'resources/js/app.ts',
            'ssr' => true,
            'pages' => 'resources/js/Pages',
            'layouts' => 'resources/js/Layouts',
            'components' => 'resources/js/Components',
        ],
    ]);

    expect($manifest->runtime)->toBe('inertia')
        ->and($manifest->frontend)->toBe([
            'entry' => 'resources/js/app.ts',
            'ssr' => true,
            'pages' => 'resources/js/Pages',
            'layouts' => 'resources/js/Layouts',
            'components' => 'resources/js/Components',
        ])
        ->and($manifest->toArray())->toHaveKey('runtime', 'inertia')
        ->and($manifest->toArray())->toHaveKey('frontend');
});

it('defaults theme runtime to blade when omitted', function (): void {
    $manifest = CapellManifestData::fromArray([
        'manifest-version' => 2,
        'name' => 'capell-app/theme-corporate',
        'kind' => 'theme',
        'capell-version' => '^4.0',
        'themeKey' => 'corporate',
    ]);

    expect($manifest->runtime)->toBe('blade')
        ->and($manifest->frontend)->toBe([])
        ->and($manifest->toArray())->toHaveKey('runtime', 'blade');
});
```

- [ ] **Step 2: Write failing manifest validator tests**

Append these tests to `/Users/ben/Sites/packages/capell/capell-4/packages/core/tests/Unit/Manifest/ManifestValidatorTest.php`:

```php
it('accepts valid frontend runtimes for theme manifests', function (string $runtime): void {
    $validator = new ManifestValidator;

    expect(fn () => $validator->validate([
        'manifest-version' => 2,
        'name' => 'capell-app/theme-test',
        'kind' => 'theme',
        'capell-version' => '^4.0',
        'runtime' => $runtime,
    ]))->not->toThrow(InvalidManifestException::class);
})->with(['blade', 'livewire', 'inertia']);

it('rejects invalid frontend runtime values', function (): void {
    $validator = new ManifestValidator;

    expect(fn () => $validator->validate([
        'manifest-version' => 2,
        'name' => 'capell-app/theme-test',
        'kind' => 'theme',
        'capell-version' => '^4.0',
        'runtime' => 'react-router',
    ]))->toThrow(InvalidManifestException::class, "invalid context: 'react-router'");
});

it('rejects non-array frontend metadata', function (): void {
    $validator = new ManifestValidator;

    expect(fn () => $validator->validate([
        'manifest-version' => 2,
        'name' => 'capell-app/theme-test',
        'kind' => 'theme',
        'capell-version' => '^4.0',
        'runtime' => 'inertia',
        'frontend' => 'resources/js/app.ts',
    ]))->toThrow(InvalidManifestException::class, 'frontend');
});
```

- [ ] **Step 3: Run tests to verify failure**

Run from `/Users/ben/Sites/packages/capell/capell-4`:

```bash
vendor/bin/pest packages/core/tests/Unit/Manifest/CapellManifestDataTest.php packages/core/tests/Unit/Manifest/ManifestValidatorTest.php --configuration=phpunit.xml
```

Expected: failures mentioning missing `runtime` or `frontend` properties.

- [ ] **Step 4: Add the runtime enum**

Create `/Users/ben/Sites/packages/capell/capell-4/packages/core/src/Enums/FrontendRuntime.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Core\Enums;

enum FrontendRuntime: string
{
    case Blade = 'blade';
    case Livewire = 'livewire';
    case Inertia = 'inertia';
}
```

- [ ] **Step 5: Update CapellManifestData**

Modify `/Users/ben/Sites/packages/capell/capell-4/packages/core/src/Support/Manifest/CapellManifestData.php`:

```php
use Capell\Core\Enums\FrontendRuntime;
```

Add constructor properties after `themeKey`:

```php
public string $runtime = FrontendRuntime::Blade->value,
public array $frontend = [],
```

In `fromArray()`, add:

```php
runtime: is_string($data['runtime'] ?? null) ? $data['runtime'] : FrontendRuntime::Blade->value,
frontend: is_array($data['frontend'] ?? null) ? $data['frontend'] : [],
```

In `toArray()`, add after `themeKey` handling:

```php
if ($this->kind === 'theme' || $this->runtime !== FrontendRuntime::Blade->value) {
    $data['runtime'] = $this->runtime;
}

if ($this->frontend !== []) {
    $data['frontend'] = $this->frontend;
}
```

- [ ] **Step 6: Update ManifestValidator**

Modify `/Users/ben/Sites/packages/capell/capell-4/packages/core/src/Support/Manifest/ManifestValidator.php`:

```php
use Capell\Core\Enums\FrontendRuntime;
```

Add this private method:

```php
private function validateRuntimeMetadata(array $data): void
{
    $runtime = $data['runtime'] ?? null;

    if ($runtime !== null && ! is_string($runtime)) {
        throw InvalidManifestException::invalidContext('runtime');
    }

    if (is_string($runtime) && FrontendRuntime::tryFrom($runtime) === null) {
        throw InvalidManifestException::invalidContext($runtime);
    }

    if (array_key_exists('frontend', $data) && ! is_array($data['frontend'])) {
        throw InvalidManifestException::missingField('frontend metadata array');
    }
}
```

Call it at the end of `validate()`:

```php
$this->validateRuntimeMetadata($data);
```

- [ ] **Step 7: Run tests to verify pass**

Run from `/Users/ben/Sites/packages/capell/capell-4`:

```bash
vendor/bin/pest packages/core/tests/Unit/Manifest/CapellManifestDataTest.php packages/core/tests/Unit/Manifest/ManifestValidatorTest.php --configuration=phpunit.xml
```

Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add packages/core/src/Enums/FrontendRuntime.php packages/core/src/Support/Manifest/CapellManifestData.php packages/core/src/Support/Manifest/ManifestValidator.php packages/core/tests/Unit/Manifest/CapellManifestDataTest.php packages/core/tests/Unit/Manifest/ManifestValidatorTest.php
git commit -m "feat(core): add frontend runtime manifest metadata"
```

## Task 2: Theme Definition Runtime Metadata

**Files:**
- Modify: `/Users/ben/Sites/packages/capell/capell-4/packages/core/src/ThemeStudio/Data/ThemeDefinitionData.php`
- Modify: `/Users/ben/Sites/packages/capell/capell-packages-4/packages/foundation-theme/tests/Unit/ThemeRegistryTest.php`
- Modify: `/Users/ben/Sites/packages/capell/capell-packages-4/packages/theme-corporate/tests/Unit/CorporateThemeDefinitionTest.php`

- [ ] **Step 1: Add failing runtime assertions**

In `/Users/ben/Sites/packages/capell/capell-packages-4/packages/theme-corporate/tests/Unit/CorporateThemeDefinitionTest.php`, add an assertion to the existing definition test:

```php
expect(CorporateThemeServiceProvider::definition()->runtime->value)->toBe('blade');
```

In `/Users/ben/Sites/packages/capell/capell-packages-4/packages/foundation-theme/tests/Unit/ThemeRegistryTest.php`, add this test:

```php
it('stores runtime metadata with theme definitions', function (): void {
    $definition = new ThemeDefinitionData(
        key: 'inertia-test',
        name: 'Inertia Test',
        description: 'Runtime-aware test theme.',
        package: 'capell-app/theme-inertia-test',
        previewImage: '/preview.jpg',
        tags: [],
        bestFit: [],
        includedSections: [],
        presets: [],
        runtime: \Capell\Core\Enums\FrontendRuntime::Inertia,
        frontend: ['entry' => 'resources/js/app.ts'],
    );

    expect($definition->runtime->value)->toBe('inertia')
        ->and($definition->frontend)->toBe(['entry' => 'resources/js/app.ts']);
});
```

- [ ] **Step 2: Run tests to verify failure**

Run from `/Users/ben/Sites/packages/capell/capell-packages-4`:

```bash
vendor/bin/pest packages/foundation-theme/tests/Unit/ThemeRegistryTest.php packages/theme-corporate/tests/Unit/CorporateThemeDefinitionTest.php --configuration=phpunit.xml
```

Expected: failure because `ThemeDefinitionData` has no `runtime` property.

- [ ] **Step 3: Add runtime fields to ThemeDefinitionData**

Modify `/Users/ben/Sites/packages/capell/capell-4/packages/core/src/ThemeStudio/Data/ThemeDefinitionData.php`:

```php
use Capell\Core\Enums\FrontendRuntime;
```

Add constructor arguments after `$assets`:

```php
public FrontendRuntime $runtime = FrontendRuntime::Blade,
public array $frontend = [],
```

Update the docblock with:

```php
 * @param  array<string, mixed>  $frontend
```

- [ ] **Step 4: Run tests to verify pass**

Run:

```bash
vendor/bin/pest packages/foundation-theme/tests/Unit/ThemeRegistryTest.php packages/theme-corporate/tests/Unit/CorporateThemeDefinitionTest.php --configuration=phpunit.xml
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
cd /Users/ben/Sites/packages/capell/capell-4
git add packages/core/src/ThemeStudio/Data/ThemeDefinitionData.php
git commit -m "feat(core): add theme definition runtime metadata"
cd /Users/ben/Sites/packages/capell/capell-packages-4
git add packages/foundation-theme/tests/Unit/ThemeRegistryTest.php packages/theme-corporate/tests/Unit/CorporateThemeDefinitionTest.php
git commit -m "test(themes): assert runtime metadata defaults"
```

## Task 3: Core Presentation Registry Contracts

**Files:**
- Create: `/Users/ben/Sites/packages/capell/capell-4/packages/core/src/ThemeStudio/Contracts/PagePresentation.php`
- Create: `/Users/ben/Sites/packages/capell/capell-4/packages/core/src/ThemeStudio/Contracts/WidgetPresentation.php`
- Create: `/Users/ben/Sites/packages/capell/capell-4/packages/core/src/ThemeStudio/Theme/PagePresentationRegistry.php`
- Create: `/Users/ben/Sites/packages/capell/capell-4/packages/core/src/ThemeStudio/Theme/WidgetPresentationRegistry.php`
- Modify: `/Users/ben/Sites/packages/capell/capell-4/packages/core/src/Providers/CapellServiceProvider.php`
- Create: `/Users/ben/Sites/packages/capell/capell-4/packages/core/tests/Unit/ThemeStudio/PresentationRegistryTest.php`

- [ ] **Step 1: Write failing registry tests**

Create `/Users/ben/Sites/packages/capell/capell-4/packages/core/tests/Unit/ThemeStudio/PresentationRegistryTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Core\Enums\FrontendRuntime;
use Capell\Core\ThemeStudio\Contracts\PagePresentation;
use Capell\Core\ThemeStudio\Contracts\WidgetPresentation;
use Capell\Core\ThemeStudio\Theme\PagePresentationRegistry;
use Capell\Core\ThemeStudio\Theme\WidgetPresentationRegistry;

it('registers page presentations by page type and runtime', function (): void {
    $presentation = new class implements PagePresentation
    {
        public function pageType(): string
        {
            return 'standard';
        }

        public function runtime(): FrontendRuntime
        {
            return FrontendRuntime::Inertia;
        }

        public function component(): string
        {
            return 'Pages/Standard';
        }
    };

    $registry = new PagePresentationRegistry;
    $registry->register($presentation);

    expect($registry->get('standard', FrontendRuntime::Inertia))->toBe($presentation)
        ->and($registry->has('standard', FrontendRuntime::Inertia))->toBeTrue()
        ->and($registry->has('standard', FrontendRuntime::Blade))->toBeFalse();
});

it('registers widget presentations by widget type and runtime', function (): void {
    $presentation = new class implements WidgetPresentation
    {
        public function widgetType(): string
        {
            return 'hero';
        }

        public function runtime(): FrontendRuntime
        {
            return FrontendRuntime::Inertia;
        }

        public function component(): string
        {
            return 'Sections/Hero';
        }
    };

    $registry = new WidgetPresentationRegistry;
    $registry->register($presentation);

    expect($registry->get('hero', FrontendRuntime::Inertia))->toBe($presentation)
        ->and($registry->has('hero', FrontendRuntime::Inertia))->toBeTrue()
        ->and($registry->has('hero', FrontendRuntime::Livewire))->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify failure**

Run:

```bash
vendor/bin/pest packages/core/tests/Unit/ThemeStudio/PresentationRegistryTest.php --configuration=phpunit.xml
```

Expected: failure because contracts/registries do not exist.

- [ ] **Step 3: Add presentation contracts**

Create `/Users/ben/Sites/packages/capell/capell-4/packages/core/src/ThemeStudio/Contracts/PagePresentation.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Core\ThemeStudio\Contracts;

use Capell\Core\Enums\FrontendRuntime;

interface PagePresentation
{
    public function pageType(): string;

    public function runtime(): FrontendRuntime;

    public function component(): string;
}
```

Create `/Users/ben/Sites/packages/capell/capell-4/packages/core/src/ThemeStudio/Contracts/WidgetPresentation.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Core\ThemeStudio\Contracts;

use Capell\Core\Enums\FrontendRuntime;

interface WidgetPresentation
{
    public function widgetType(): string;

    public function runtime(): FrontendRuntime;

    public function component(): string;
}
```

- [ ] **Step 4: Add registries**

Create `/Users/ben/Sites/packages/capell/capell-4/packages/core/src/ThemeStudio/Theme/PagePresentationRegistry.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Core\ThemeStudio\Theme;

use Capell\Core\Enums\FrontendRuntime;
use Capell\Core\ThemeStudio\Contracts\PagePresentation;

final class PagePresentationRegistry
{
    /** @var array<string, array<string, PagePresentation>> */
    private array $presentations = [];

    public function register(PagePresentation $presentation): void
    {
        $this->presentations[$presentation->pageType()][$presentation->runtime()->value] = $presentation;
    }

    public function get(string $pageType, FrontendRuntime $runtime): ?PagePresentation
    {
        return $this->presentations[$pageType][$runtime->value] ?? null;
    }

    public function has(string $pageType, FrontendRuntime $runtime): bool
    {
        return $this->get($pageType, $runtime) instanceof PagePresentation;
    }
}
```

Create `/Users/ben/Sites/packages/capell/capell-4/packages/core/src/ThemeStudio/Theme/WidgetPresentationRegistry.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Core\ThemeStudio\Theme;

use Capell\Core\Enums\FrontendRuntime;
use Capell\Core\ThemeStudio\Contracts\WidgetPresentation;

final class WidgetPresentationRegistry
{
    /** @var array<string, array<string, WidgetPresentation>> */
    private array $presentations = [];

    public function register(WidgetPresentation $presentation): void
    {
        $this->presentations[$presentation->widgetType()][$presentation->runtime()->value] = $presentation;
    }

    public function get(string $widgetType, FrontendRuntime $runtime): ?WidgetPresentation
    {
        return $this->presentations[$widgetType][$runtime->value] ?? null;
    }

    public function has(string $widgetType, FrontendRuntime $runtime): bool
    {
        return $this->get($widgetType, $runtime) instanceof WidgetPresentation;
    }
}
```

- [ ] **Step 5: Bind registries**

Modify `/Users/ben/Sites/packages/capell/capell-4/packages/core/src/Providers/CapellServiceProvider.php`.

Add imports:

```php
use Capell\Core\ThemeStudio\Theme\PagePresentationRegistry;
use Capell\Core\ThemeStudio\Theme\WidgetPresentationRegistry;
```

Near the existing `ThemeRegistry` singleton binding, add:

```php
$this->app->singleton(PagePresentationRegistry::class);
$this->app->singleton(WidgetPresentationRegistry::class);
```

- [ ] **Step 6: Run tests**

Run:

```bash
vendor/bin/pest packages/core/tests/Unit/ThemeStudio/PresentationRegistryTest.php --configuration=phpunit.xml
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add packages/core/src/ThemeStudio/Contracts/PagePresentation.php packages/core/src/ThemeStudio/Contracts/WidgetPresentation.php packages/core/src/ThemeStudio/Theme/PagePresentationRegistry.php packages/core/src/ThemeStudio/Theme/WidgetPresentationRegistry.php packages/core/src/Providers/CapellServiceProvider.php packages/core/tests/Unit/ThemeStudio/PresentationRegistryTest.php
git commit -m "feat(core): add frontend presentation registries"
```

## Task 4: Frontend Response Renderer Registry

**Files:**
- Create: `/Users/ben/Sites/packages/capell/capell-4/packages/frontend/src/Contracts/FrontendResponseRenderer.php`
- Create: `/Users/ben/Sites/packages/capell/capell-4/packages/frontend/src/Support/Render/FrontendResponseRendererRegistry.php`
- Create: `/Users/ben/Sites/packages/capell/capell-4/packages/frontend/tests/Unit/Render/FrontendResponseRendererRegistryTest.php`

- [ ] **Step 1: Write failing registry test**

Create `/Users/ben/Sites/packages/capell/capell-4/packages/frontend/tests/Unit/Render/FrontendResponseRendererRegistryTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Core\Enums\FrontendRuntime;
use Capell\Frontend\Contracts\FrontendResponseRenderer;
use Capell\Frontend\Support\Render\FrontendResponseRendererRegistry;
use Illuminate\Http\Response;

it('registers and resolves response renderers by runtime', function (): void {
    $renderer = new class implements FrontendResponseRenderer
    {
        public function runtime(): FrontendRuntime
        {
            return FrontendRuntime::Livewire;
        }

        public function render(?int $status = null): Response
        {
            return response('livewire', $status ?? 200);
        }
    };

    $registry = new FrontendResponseRendererRegistry;
    $registry->register($renderer);

    expect($registry->forRuntime(FrontendRuntime::Livewire))->toBe($renderer)
        ->and($registry->has(FrontendRuntime::Livewire))->toBeTrue()
        ->and($registry->has(FrontendRuntime::Inertia))->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify failure**

Run:

```bash
vendor/bin/pest packages/frontend/tests/Unit/Render/FrontendResponseRendererRegistryTest.php --configuration=phpunit.xml
```

Expected: failure because the contract and registry do not exist.

- [ ] **Step 3: Add renderer contract**

Create `/Users/ben/Sites/packages/capell/capell-4/packages/frontend/src/Contracts/FrontendResponseRenderer.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Frontend\Contracts;

use Capell\Core\Enums\FrontendRuntime;
use Illuminate\Http\Response;

interface FrontendResponseRenderer
{
    public function runtime(): FrontendRuntime;

    public function render(?int $status = null): Response;
}
```

- [ ] **Step 4: Add renderer registry**

Create `/Users/ben/Sites/packages/capell/capell-4/packages/frontend/src/Support/Render/FrontendResponseRendererRegistry.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Frontend\Support\Render;

use Capell\Core\Enums\FrontendRuntime;
use Capell\Frontend\Contracts\FrontendResponseRenderer;

final class FrontendResponseRendererRegistry
{
    /** @var array<string, FrontendResponseRenderer> */
    private array $renderers = [];

    public function register(FrontendResponseRenderer $renderer): void
    {
        $this->renderers[$renderer->runtime()->value] = $renderer;
    }

    public function forRuntime(FrontendRuntime $runtime): ?FrontendResponseRenderer
    {
        return $this->renderers[$runtime->value] ?? null;
    }

    public function has(FrontendRuntime $runtime): bool
    {
        return $this->forRuntime($runtime) instanceof FrontendResponseRenderer;
    }
}
```

- [ ] **Step 5: Run test**

Run:

```bash
vendor/bin/pest packages/frontend/tests/Unit/Render/FrontendResponseRendererRegistryTest.php --configuration=phpunit.xml
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/frontend/src/Contracts/FrontendResponseRenderer.php packages/frontend/src/Support/Render/FrontendResponseRendererRegistry.php packages/frontend/tests/Unit/Render/FrontendResponseRendererRegistryTest.php
git commit -m "feat(frontend): add response renderer registry"
```

## Task 5: Livewire Renderer Adapter And Controller Delegation

**Files:**
- Create: `/Users/ben/Sites/packages/capell/capell-4/packages/frontend/src/Support/Render/LivewireFrontendResponseRenderer.php`
- Modify: `/Users/ben/Sites/packages/capell/capell-4/packages/frontend/src/Http/Controllers/PageController.php`
- Modify: `/Users/ben/Sites/packages/capell/capell-4/packages/frontend/src/Providers/FrontendServiceProvider.php`
- Create: `/Users/ben/Sites/packages/capell/capell-4/packages/frontend/tests/Unit/Render/LivewireFrontendResponseRendererTest.php`

- [ ] **Step 1: Write failing Livewire renderer test**

Create `/Users/ben/Sites/packages/capell/capell-4/packages/frontend/tests/Unit/Render/LivewireFrontendResponseRendererTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Core\Enums\FrontendRuntime;
use Capell\Frontend\Support\Render\LivewireFrontendResponseRenderer;

it('declares the livewire runtime', function (): void {
    $renderer = new LivewireFrontendResponseRenderer;

    expect($renderer->runtime())->toBe(FrontendRuntime::Livewire);
});
```

- [ ] **Step 2: Run test to verify failure**

Run:

```bash
vendor/bin/pest packages/frontend/tests/Unit/Render/LivewireFrontendResponseRendererTest.php --configuration=phpunit.xml
```

Expected: failure because the renderer does not exist.

- [ ] **Step 3: Add Livewire renderer**

Create `/Users/ben/Sites/packages/capell/capell-4/packages/frontend/src/Support/Render/LivewireFrontendResponseRenderer.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Frontend\Support\Render;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\FrontendRuntime;
use Capell\Core\Enums\LivewirePageComponentEnum;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Contracts\FrontendResponseRenderer;
use Capell\Frontend\Support\Loader\PageLoader;
use Illuminate\Http\Response;
use Livewire\Component;

final class LivewireFrontendResponseRenderer implements FrontendResponseRenderer
{
    public function runtime(): FrontendRuntime
    {
        return FrontendRuntime::Livewire;
    }

    public function render(?int $status = null): Response
    {
        $context = resolve(FrontendContextReader::class);
        $page = $context->page();

        if (! $page instanceof Pageable) {
            $site = $context->site();
            $language = $context->language();
            $page = $site !== null && $language !== null
                ? PageLoader::getErrorPage($site, $language)
                : null;
        }

        if (! $page instanceof Pageable) {
            return response()->noContent($status ?? 404);
        }

        $component = $this->componentFor($page);
        $instance = resolve('livewire')->new($component);
        $response = app()->call([$instance, '__invoke']);

        if ($status !== null) {
            $response->setStatusCode($status);
        }

        return $response;
    }

    /**
     * @return class-string<Component>
     */
    private function componentFor(Pageable $page): string
    {
        return $page->type->meta['component'] ?? LivewirePageComponentEnum::Default->value;
    }
}
```

- [ ] **Step 4: Register renderer in provider**

Modify `/Users/ben/Sites/packages/capell/capell-4/packages/frontend/src/Providers/FrontendServiceProvider.php`.

Add imports:

```php
use Capell\Frontend\Support\Render\FrontendResponseRendererRegistry;
use Capell\Frontend\Support\Render\LivewireFrontendResponseRenderer;
```

In `packageRegistered()`, add singleton binding near other render services:

```php
$this->app->singleton(FrontendResponseRendererRegistry::class);
```

Add after resolving hook:

```php
$this->app->afterResolving(
    FrontendResponseRendererRegistry::class,
    function (FrontendResponseRendererRegistry $registry): void {
        $registry->register($this->app->make(LivewireFrontendResponseRenderer::class));
    },
);
```

- [ ] **Step 5: Delegate PageController page rendering to registry**

Modify `/Users/ben/Sites/packages/capell/capell-4/packages/frontend/src/Http/Controllers/PageController.php`.

Add imports:

```php
use Capell\Core\Enums\FrontendRuntime;
use Capell\Frontend\Support\Render\FrontendResponseRendererRegistry;
```

Replace calls to `renderPageComponent()` with:

```php
return $this->renderFrontendResponse();
```

For error page responses, use:

```php
return $this->renderFrontendResponse(SymfonyResponse::HTTP_NOT_FOUND);
```

Add this method:

```php
private function renderFrontendResponse(?int $status = null): Response
{
    $renderer = resolve(FrontendResponseRendererRegistry::class)
        ->forRuntime(FrontendRuntime::Livewire);

    if ($renderer === null) {
        return response()->noContent($status ?? 404);
    }

    return $renderer->render($status);
}
```

Remove `renderPageComponent()` and `getLivewireComponent()` from the controller after the renderer owns that logic. Remove unused imports `Pageable`, `LivewirePageComponentEnum`, and `Livewire\Component`.

- [ ] **Step 6: Run focused frontend tests**

Run:

```bash
vendor/bin/pest packages/frontend/tests/Unit/Render/LivewireFrontendResponseRendererTest.php packages/frontend/tests/Unit/Render/FrontendResponseRendererRegistryTest.php packages/frontend/tests/Feature/PageResolverTest.php --configuration=phpunit.xml
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add packages/frontend/src/Contracts/FrontendResponseRenderer.php packages/frontend/src/Support/Render/FrontendResponseRendererRegistry.php packages/frontend/src/Support/Render/LivewireFrontendResponseRenderer.php packages/frontend/src/Http/Controllers/PageController.php packages/frontend/src/Providers/FrontendServiceProvider.php packages/frontend/tests/Unit/Render/LivewireFrontendResponseRendererTest.php packages/frontend/tests/Unit/Render/FrontendResponseRendererRegistryTest.php
git commit -m "feat(frontend): route public pages through runtime renderers"
```

## Task 6: Verification

**Files:**
- Verify changed files in `/Users/ben/Sites/packages/capell/capell-4`.

- [ ] **Step 1: Run core runtime tests**

Run:

```bash
cd /Users/ben/Sites/packages/capell/capell-4
vendor/bin/pest packages/core/tests/Unit/Manifest/CapellManifestDataTest.php packages/core/tests/Unit/Manifest/ManifestValidatorTest.php packages/core/tests/Unit/ThemeStudio/PresentationRegistryTest.php --configuration=phpunit.xml
cd /Users/ben/Sites/packages/capell/capell-packages-4
vendor/bin/pest packages/foundation-theme/tests/Unit/ThemeRegistryTest.php packages/theme-corporate/tests/Unit/CorporateThemeDefinitionTest.php --configuration=phpunit.xml
```

Expected: PASS.

- [ ] **Step 2: Run frontend renderer tests**

Run:

```bash
vendor/bin/pest packages/frontend/tests/Unit/Render/FrontendResponseRendererRegistryTest.php packages/frontend/tests/Unit/Render/LivewireFrontendResponseRendererTest.php packages/frontend/tests/Feature/PageResolverTest.php --configuration=phpunit.xml
```

Expected: PASS.

- [ ] **Step 3: Run formatting on changed files**

Run:

```bash
vendor/bin/pint packages/core/src/Enums/FrontendRuntime.php packages/core/src/Support/Manifest/CapellManifestData.php packages/core/src/Support/Manifest/ManifestValidator.php packages/core/src/ThemeStudio/Data/ThemeDefinitionData.php packages/core/src/ThemeStudio/Contracts/PagePresentation.php packages/core/src/ThemeStudio/Contracts/WidgetPresentation.php packages/core/src/ThemeStudio/Theme/PagePresentationRegistry.php packages/core/src/ThemeStudio/Theme/WidgetPresentationRegistry.php packages/core/src/Providers/CapellServiceProvider.php packages/frontend/src/Contracts/FrontendResponseRenderer.php packages/frontend/src/Support/Render/FrontendResponseRendererRegistry.php packages/frontend/src/Support/Render/LivewireFrontendResponseRenderer.php packages/frontend/src/Http/Controllers/PageController.php packages/frontend/src/Providers/FrontendServiceProvider.php
```

Expected: PASS or files formatted with no unrelated changes.

- [ ] **Step 4: Inspect git diff**

Run:

```bash
git diff --stat
git diff --check
```

Expected: only files from this plan are changed, and `git diff --check` reports no whitespace errors.

- [ ] **Step 5: Final commit if formatting changed files**

If Pint changed files after prior task commits:

```bash
git add packages/core packages/frontend
git commit -m "style: format frontend runtime contract"
```

Expected: commit created only if there were formatting changes.
