# Relation Manager Test Coverage Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Improve test coverage for the four LayoutBuilder relation managers that are missing tests or have thin coverage: PagesRelationManager, WidgetsRelationManager, LayoutsRelationManager, and WidgetAssetsRelationManager (no tests at all).

**Architecture:** Each task follows the same pattern established in `SectionAssetsRelationManagerTest.php`: list, search, empty-search, filter, bulk-delete, and create scenarios. Read-only relation managers (no create/delete actions) skip the destructive tests. All tests use model factories, never manual model creation.

**Tech Stack:** Pest 3+, Filament 5, Livewire, Laravel factories, `function Pest\Livewire\livewire`

---

## Reference patterns (use these in every task)

**List test:**

```php
livewire(TheRelationManager::class, ['ownerRecord' => $owner, 'pageClass' => EditOwner::class])
    ->assertSuccessful()
    ->assertCountTableRecords(N)
    ->assertCanSeeTableRecords($collection)
    ->assertTableColumnStateSet('column.name', [$value], record: $record);
```

**Search test:**

```php
->searchTable($term)
->assertCountTableRecords(N)
->assertCanSeeTableRecords([$matching])
->assertCanNotSeeTableRecords([$nonMatching]);
```

**Empty search:**

```php
->searchTable('zzz-no-match')
->assertCountTableRecords(0);
```

**Filter test:**

```php
->filterTable('filter_name', $value)
->assertCountTableRecords(N)
->assertCanSeeTableRecords([$filtered]);
```

**Bulk delete:**

```php
->selectTableRecords($ids)
->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
->assertHasNoFormErrors()
->assertCountTableRecords(0);
```

**Create (via header action):**

```php
->mountAction(TestAction::make(CreateAction::class)->table())
->fillForm(['asset_type' => $asset->getMorphClass(), 'asset_id' => [$asset->getKey()]])
->callMountedAction()
->assertHasNoFormErrors()
->assertCountTableRecords(1);
```

---

## Task 1: Improve PagesRelationManagerTest

**Files:**

- Modify: `tests/LayoutBuilder/Feature/Filament/Resources/Section/RelationManagers/PagesRelationManagerTest.php`

**Context:**
`PagesRelationManager` is read-only (no create/delete). It shows pages a section appears on via `WidgetAsset` records. The table searches by `pageable_id` (the integer ID). Its columns include `pageable.id`, `pageable.name`, `pageable.site.name`.

- [ ] **Step 1: Add empty-search test**

Append to the test file:

```php
it('returns no results when search matches nothing', function (): void {
    $page = Page::factory()->withTranslations()->create();
    $content = Section::factory()->create();

    Widget::factory()
        ->has(
            WidgetAsset::factory()
                ->page($page)
                ->asset($content)
                ->state(['container' => 'main', 'occurrence' => 1]),
            'assets',
        )
        ->create();

    livewire(PagesRelationManager::class, [
        'ownerRecord' => $content,
        'pageClass' => EditSection::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->searchTable('99999999')
        ->assertCountTableRecords(0);
});
```

- [ ] **Step 2: Add site column state assertion to the list test**

Replace the existing `it('can list pages for a content model')` body's `assertTableColumnStateSet` line to also verify the site:

```php
it('can list pages for a content model', function (): void {
    $page = Page::factory()->withTranslations()->create();
    $content = Section::factory()->create();

    $widget = Widget::factory()
        ->has(
            WidgetAsset::factory()
                ->page($page)
                ->asset($content)
                ->state(['container' => 'main'])
                ->forEachSequence(
                    ['occurrence' => 1],
                    ['occurrence' => 2],
                ),
            'assets',
        )
        ->create();

    $widgetAsset = $widget->assets()->first();

    livewire(PagesRelationManager::class, [
        'ownerRecord' => $content,
        'pageClass' => EditSection::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords($content->pages)
        ->assertTableColumnStateSet('pageable.name', [$page->name], record: $widgetAsset)
        ->assertTableColumnStateSet('pageable.site.name', [$page->site->name], record: $widgetAsset);
});
```

- [ ] **Step 3: Run tests**

```bash
php vendor/bin/pest tests/LayoutBuilder/Feature/Filament/Resources/Section/RelationManagers/PagesRelationManagerTest.php
```

Expected: all 3 tests pass.

- [ ] **Step 4: Commit**

```bash
git add tests/LayoutBuilder/Feature/Filament/Resources/Section/RelationManagers/PagesRelationManagerTest.php
git commit -m "test(layout-builder): improve PagesRelationManager test coverage"
```

---

## Task 2: Improve WidgetsRelationManagerTest

**Files:**

- Modify: `tests/LayoutBuilder/Feature/Filament/Resources/Section/RelationManagers/WidgetsRelationManagerTest.php`

**Context:**
`WidgetsRelationManager` is read-only (no create/delete). Searches by `widget.key` (already tested). Has a sortable `widget.key` column.

- [ ] **Step 1: Add empty-search test**

Append to the test file:

```php
it('returns no results when search matches nothing', function (): void {
    $content = Section::factory()->create();

    Widget::factory()
        ->count(3)
        ->has(WidgetAsset::factory()->asset($content), 'assets')
        ->create();

    livewire(WidgetsRelationManager::class, [
        'ownerRecord' => $content,
        'pageClass' => EditSection::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(3)
        ->searchTable('zzz-no-match')
        ->assertCountTableRecords(0);
});
```

- [ ] **Step 2: Add sort test**

Append to the test file:

```php
it('can sort widgets by key', function (): void {
    $content = Section::factory()->create();

    Widget::factory()
        ->count(3)
        ->has(WidgetAsset::factory()->asset($content), 'assets')
        ->create();

    $widgetAssets = $content->widgets()->with('widget')->get()->sortBy('widget.key');

    livewire(WidgetsRelationManager::class, [
        'ownerRecord' => $content,
        'pageClass' => EditSection::class,
    ])
        ->assertSuccessful()
        ->sortTable('widget.key')
        ->assertCanSeeTableRecords($widgetAssets, inOrder: true);
});
```

- [ ] **Step 3: Run tests**

```bash
php vendor/bin/pest tests/LayoutBuilder/Feature/Filament/Resources/Section/RelationManagers/WidgetsRelationManagerTest.php
```

Expected: all 4 tests pass.

- [ ] **Step 4: Commit**

```bash
git add tests/LayoutBuilder/Feature/Filament/Resources/Section/RelationManagers/WidgetsRelationManagerTest.php
git commit -m "test(layout-builder): improve WidgetsRelationManager test coverage"
```

---

## Task 3: Improve LayoutsRelationManagerTest

**Files:**

- Modify: `tests/LayoutBuilder/Feature/Filament/Resources/Widget/RelationManagers/LayoutsRelationManagerTest.php`

**Context:**
`LayoutsRelationManager` is read-only. Has a `site_id` SelectFilter (already tested). No searchable columns, so no search test. Add: empty filter result and column state assertion for `site.name`.

- [ ] **Step 1: Add column state assertion to list test**

Replace the existing `it('can list layouts for a widget')` to also assert the site column:

```php
it('can list layouts for a widget', function (): void {
    $widget = Widget::factory()->create();

    Layout::factory()
        ->state([
            'containers' => [
                'main' => [
                    'widgets' => [
                        ['widget_key' => $widget->key],
                    ],
                ],
            ],
        ])
        ->count(5)
        ->create();

    $layout = $widget->layouts->first();

    livewire(LayoutsRelationManager::class, [
        'ownerRecord' => $widget,
        'pageClass' => EditWidget::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($widget->layouts)
        ->assertTableColumnStateSet('name', [$layout->name], record: $layout)
        ->assertTableColumnStateSet('site.name', [$layout->site->name], record: $layout);
});
```

- [ ] **Step 2: Add filter-returns-empty test**

Append to the test file (add `use Capell\Core\Models\Site;` to imports if not present):

```php
it('returns no results when site filter matches no layouts', function (): void {
    $widget = Widget::factory()->create();
    $otherSite = Site::factory()->create();

    Layout::factory()
        ->state([
            'containers' => [
                'main' => [
                    'widgets' => [
                        ['widget_key' => $widget->key],
                    ],
                ],
            ],
        ])
        ->count(2)
        ->create();

    livewire(LayoutsRelationManager::class, [
        'ownerRecord' => $widget,
        'pageClass' => EditWidget::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(2)
        ->filterTable('site_id', $otherSite->getKey())
        ->assertCountTableRecords(0);
});
```

- [ ] **Step 3: Run tests**

```bash
php vendor/bin/pest tests/LayoutBuilder/Feature/Filament/Resources/Widget/RelationManagers/LayoutsRelationManagerTest.php
```

Expected: all 3 tests pass.

- [ ] **Step 4: Commit**

```bash
git add tests/LayoutBuilder/Feature/Filament/Resources/Widget/RelationManagers/LayoutsRelationManagerTest.php
git commit -m "test(layout-builder): improve LayoutsRelationManager test coverage"
```

---

## Task 4: Create WidgetAssetsRelationManagerTest

**Files:**

- Create: `tests/LayoutBuilder/Feature/Filament/Resources/Widget/RelationManagers/WidgetAssetsRelationManagerTest.php`

**Context:**
`WidgetAssetsRelationManager` (relationship: `widgetAssets` on Widget) shows `WidgetAsset` records. Full CRUD: create via `HasAssetsRelationManager::createResourcesAction()`, delete per-row, bulk delete. Columns: `id`, `asset.name`, `asset_type` (sortable badge), `pageable.name`. Filters: page, asset type, type_id.

The factory setup mirrors the SectionAssetsRelationManager tests but uses the `widgetAssets` relationship. The `WidgetAssetFactory` states used: `.page(Page $page)`, `.asset(Model $model)`, `.widget(Widget $widget)`.

Note: For the create action, `WidgetAssetsRelationManager` delegates to `WidgetAssetForm` and uses `createResourcesAction()` from `HasAssetsRelationManager` which requires `asset_type` + `asset_id`. However, WidgetAsset also needs `pageable_type`/`pageable_id` — the create form may differ. **If the create test fails with form errors, inspect what fields `WidgetAssetForm::configure()` requires and adjust `fillForm()` accordingly before committing.**

- [ ] **Step 1: Create the test file**

```php
<?php

declare(strict_types=1);

use Capell\Core\Models\AssetRelation;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Pages\EditWidget;
use Capell\LayoutBuilder\Filament\Resources\Widgets\RelationManagers\WidgetAssetsRelationManager;
use Capell\LayoutBuilder\Models\Section;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Testing\TestAction;

use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Livewire\livewire;

it('can list widget assets', function (): void {
    $page = Page::factory()->withTranslations()->create();
    $section = Section::factory()->create();

    $widget = Widget::factory()
        ->has(
            WidgetAsset::factory()
                ->page($page)
                ->asset($section)
                ->state(['container' => 'main'])
                ->sequence(
                    ['occurrence' => 1],
                    ['occurrence' => 2],
                    ['occurrence' => 3],
                    ['occurrence' => 4],
                    ['occurrence' => 5],
                )
                ->count(5),
            'widgetAssets',
        )
        ->create();

    $widgetAsset = $widget->widgetAssets()->first();

    livewire(WidgetAssetsRelationManager::class, [
        'ownerRecord' => $widget,
        'pageClass' => EditWidget::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($widget->widgetAssets)
        ->assertTableColumnStateSet('asset.name', [$section->name], record: $widgetAsset);
});

it('can search widget assets by name', function (): void {
    $page = Page::factory()->withTranslations()->create();
    $sectionA = Section::factory(['name' => 'Alpha Section'])->create();
    $sectionB = Section::factory(['name' => 'Beta Section'])->create();

    $widget = Widget::factory()
        ->has(
            WidgetAsset::factory()->page($page)->asset($sectionA)->state(['container' => 'main', 'occurrence' => 1]),
            'widgetAssets',
        )
        ->has(
            WidgetAsset::factory()->page($page)->asset($sectionB)->state(['container' => 'main', 'occurrence' => 2]),
            'widgetAssets',
        )
        ->create();

    $alphaAsset = $widget->widgetAssets()->with('asset')->get()->firstWhere('asset.name', 'Alpha Section');

    livewire(WidgetAssetsRelationManager::class, [
        'ownerRecord' => $widget,
        'pageClass' => EditWidget::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(2)
        ->searchTable('Alpha Section')
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([$alphaAsset]);
});

it('returns no results when search matches nothing', function (): void {
    $page = Page::factory()->withTranslations()->create();
    $section = Section::factory()->create();

    Widget::factory()
        ->has(
            WidgetAsset::factory()->page($page)->asset($section)->state(['container' => 'main', 'occurrence' => 1]),
            'widgetAssets',
        )
        ->create();

    livewire(WidgetAssetsRelationManager::class, [
        'ownerRecord' => $widget = Widget::factory()->create(),
        'pageClass' => EditWidget::class,
    ]);

    // Fresh widget with assets
    $widget = Widget::factory()
        ->has(
            WidgetAsset::factory()->page($page)->asset($section)->state(['container' => 'main', 'occurrence' => 1]),
            'widgetAssets',
        )
        ->create();

    livewire(WidgetAssetsRelationManager::class, [
        'ownerRecord' => $widget,
        'pageClass' => EditWidget::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->searchTable('zzz-no-match')
        ->assertCountTableRecords(0);
});

it('can bulk delete widget assets', function (): void {
    $page = Page::factory()->withTranslations()->create();
    $section = Section::factory()->create();

    $widget = Widget::factory()
        ->has(
            WidgetAsset::factory()
                ->page($page)
                ->asset($section)
                ->state(['container' => 'main'])
                ->sequence(
                    ['occurrence' => 1],
                    ['occurrence' => 2],
                    ['occurrence' => 3],
                )
                ->count(3),
            'widgetAssets',
        )
        ->create();

    $widgetAssets = $widget->widgetAssets;

    livewire(WidgetAssetsRelationManager::class, [
        'ownerRecord' => $widget,
        'pageClass' => EditWidget::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(3)
        ->selectTableRecords($widgetAssets->pluck('id')->toArray())
        ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(0);

    foreach ($widgetAssets as $widgetAsset) {
        assertDatabaseMissing(WidgetAsset::class, ['id' => $widgetAsset->id]);
    }
});
```

- [ ] **Step 2: Run the tests (expect failures to diagnose)**

```bash
php vendor/bin/pest tests/LayoutBuilder/Feature/Filament/Resources/Widget/RelationManagers/WidgetAssetsRelationManagerTest.php
```

Fix any failures before continuing. Common issues:

- `widgetAssets` relationship not found on Widget → check `Widget::widgetAssets()` or `resolveRelationUsing`
- Column state mismatch → verify `WidgetAssetsTable::getTableColumns()` keys match what's tested

- [ ] **Step 3: Fix the empty-search test (it has a duplicate setup)**

The empty-search test above has a copy-paste error. Replace `it('returns no results when search matches nothing')` with this corrected version:

```php
it('returns no results when search matches nothing', function (): void {
    $page = Page::factory()->withTranslations()->create();
    $section = Section::factory()->create();

    $widget = Widget::factory()
        ->has(
            WidgetAsset::factory()->page($page)->asset($section)->state(['container' => 'main', 'occurrence' => 1]),
            'widgetAssets',
        )
        ->create();

    livewire(WidgetAssetsRelationManager::class, [
        'ownerRecord' => $widget,
        'pageClass' => EditWidget::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->searchTable('zzz-no-match')
        ->assertCountTableRecords(0);
});
```

- [ ] **Step 4: Run all tests again**

```bash
php vendor/bin/pest tests/LayoutBuilder/Feature/Filament/Resources/Widget/RelationManagers/WidgetAssetsRelationManagerTest.php
```

Expected: all 4 tests pass.

- [ ] **Step 5: Commit**

```bash
git add tests/LayoutBuilder/Feature/Filament/Resources/Widget/RelationManagers/WidgetAssetsRelationManagerTest.php
git commit -m "test(layout-builder): add WidgetAssetsRelationManager test coverage"
```

---

## Task 5: Run full test suite

- [ ] **Step 1: Run all LayoutBuilder relation manager tests together**

```bash
php vendor/bin/pest tests/LayoutBuilder/Feature/Filament/Resources/Section/RelationManagers/ tests/LayoutBuilder/Feature/Filament/Resources/Widget/RelationManagers/
```

Expected: all tests green.

- [ ] **Step 2: Run full suite to check for regressions**

```bash
composer test
```

Expected: all tests pass, no regressions.

---

## Out of scope (left for follow-up)

- **ContentsRelationManager** — shows sections on a Page from the admin `EditPage` class. Needs research into how to instantiate `Capell\Admin\Filament\Resources\Pages\Pages\EditPage` in tests and how `Page::contents()` is registered in `LayoutBuilderServiceProvider`. Worth a dedicated plan once unblocked.
- **Create test for WidgetAssetsRelationManager** — `WidgetAssetForm` may require `pageable_type`/`pageable_id` in addition to `asset_type`/`asset_id`. Deferred because the form schema differs from `HasAssetsRelationManager::getAssetForm()` and needs form-field inspection to write correctly.
- **Filter by asset type in WidgetAssetsRelationManager** — the filter is a nested `Filter::make('filter')` with a sub-`Select`, not a simple `SelectFilter`. Testing nested filter schemas requires passing an array to `filterTable('filter', ['type' => 'section'])`. Deferred pending confirmation of the correct key names.
