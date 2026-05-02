# Dashboard Missing Widgets Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add the widgets visible in the target mockup that are currently missing from the live dashboard.

**Architecture:** New widgets follow the established `CapellWidget` → `Data` class → Blade view pattern. The combined chart widget merges two existing admin chart widgets into a single view. Most new widgets belong in the blog package since they concern content and traffic analytics. A cross-package "recent activity" widget belongs in mosaic as it can surface content from any package.

**Tech Stack:** PHP 8.2, Laravel 10, Filament 5, Pest 4, Spatie Laravel Data, Tailwind CSS 4

---

## What's missing (target vs current)

| Widget                            | Target label                                                        | Lives in                       |
| --------------------------------- | ------------------------------------------------------------------- | ------------------------------ |
| Date filter as tab pills          | _(replaces dropdown)_                                               | core admin — out of scope here |
| Stats sparklines + "vs last week" | `STATSOVERVIEWWIDGET WITH TRENDS`                                   | core admin — out of scope here |
| Combined traffic chart            | `TOTALACCESSLOGSCHARTWIDGET + TOTALVISITORSCHARTWIDGET → COMBINED`  | blog package                   |
| Top pages                         | _(trophy icon, page paths + counts)_                                | blog package                   |
| Recent activity feed              | _(Published / Draft / Review badges)_                               | mosaic package                 |
| Combined health widget            | `ALERTSWIDGET + SETUPHEALTHWIDGET + CONTENTHEALTHWIDGET → COMBINED` | core admin — out of scope here |
| Developer dashboard header button | `CACHEHEALTHWIDGET → DEVELOPER DASHBOARD`                           | core admin — out of scope here |

> **Note:** The date filter tabs, stats trends, combined health widget, and developer dashboard button are annotations on core admin widgets (from `capell-app/capell`). Those changes need a matching plan in the core repo. This plan covers only what belongs in `capell-packages-4`.

---

## File map

### New files

- `packages/blog/src/Filament/Widgets/TrafficChartWidget.php` — combined views + visitors chart
- `packages/blog/src/Data/Dashboard/TrafficChartData.php` — daily views/visitors series data
- `packages/blog/src/Data/Dashboard/TrafficPointData.php` — single data point (date, views, visitors)
- `packages/blog/resources/views/filament/widgets/traffic-chart.blade.php` — chart Blade view
- `packages/blog/src/Filament/Widgets/TopPagesWidget.php` — top pages by view count
- `packages/blog/src/Data/Dashboard/TopPagesData.php` — list of top pages
- `packages/blog/src/Data/Dashboard/TopPageData.php` — single page entry (path, views)
- `packages/blog/resources/views/filament/widgets/top-pages.blade.php` — top pages Blade view
- `packages/mosaic/src/Filament/Widgets/RecentActivityWidget.php` — cross-content recent activity feed
- `packages/mosaic/src/Data/Dashboard/RecentActivityData.php` — list of activity items
- `packages/mosaic/src/Data/Dashboard/ActivityItemData.php` — single item (title, type, status, updated_at)
- `packages/mosaic/resources/views/filament/widgets/recent-activity.blade.php` — activity feed Blade view

### Modified files

- `packages/blog/src/Providers/BlogServiceProvider.php` — register new widgets
- `packages/mosaic/src/Providers/MosaicServiceProvider.php` — register RecentActivityWidget
- `tests/src/Blog/Feature/Filament/Widgets/TrafficChartWidgetTest.php` — new test
- `tests/src/Blog/Feature/Filament/Widgets/TopPagesWidgetTest.php` — new test
- `tests/src/Mosaic/Feature/Filament/Widgets/RecentActivityWidgetTest.php` — new test

---

## Task 1: TrafficChartData + TrafficPointData

**Files:**

- Create: `packages/blog/src/Data/Dashboard/TrafficPointData.php`
- Create: `packages/blog/src/Data/Dashboard/TrafficChartData.php`

- [ ] **Step 1: Create `TrafficPointData`**

```php
<?php

declare(strict_types=1);

namespace Capell\Blog\Data\Dashboard;

use Spatie\LaravelData\Data;

final class TrafficPointData extends Data
{
    public function __construct(
        public readonly string $date,
        public readonly int $views,
        public readonly int $visitors,
    ) {}
}
```

- [ ] **Step 2: Create `TrafficChartData`**

```php
<?php

declare(strict_types=1);

namespace Capell\Blog\Data\Dashboard;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

final class TrafficChartData extends Data
{
    /**
     * @param  Collection<int, TrafficPointData>  $points
     */
    public function __construct(
        public readonly int $totalViews,
        public readonly int $totalVisitors,
        public readonly Collection $points,
    ) {}
}
```

- [ ] **Step 3: Commit**

```bash
git add packages/blog/src/Data/Dashboard/TrafficPointData.php packages/blog/src/Data/Dashboard/TrafficChartData.php
git commit -m "feat(blog): add TrafficChartData and TrafficPointData"
```

---

## Task 2: TrafficChartWidget + view

**Files:**

- Create: `packages/blog/src/Filament/Widgets/TrafficChartWidget.php`
- Create: `packages/blog/resources/views/filament/widgets/traffic-chart.blade.php`
- Create: `tests/src/Blog/Feature/Filament/Widgets/TrafficChartWidgetTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Capell\Blog\Filament\Widgets\TrafficChartWidgetAbstract;
use Capell\Core\Models\AccessLog;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('widget');

it('renders for an admin user', function (): void {
    test()->actingAsAdmin();
    livewire(TrafficChartWidgetAbstract::class)->assertOk();
});

it('shows total views when access logs exist', function (): void {
    test()->actingAsAdmin();
    AccessLog::factory()->count(3)->create();
    livewire(TrafficChartWidgetAbstract::class)
        ->assertOk()
        ->assertSee('Site traffic');
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php vendor/bin/pest tests/src/Blog/Feature/Filament/Widgets/TrafficChartWidgetTest.php -v
```

Expected: FAIL — `TrafficChartWidget` not found.

- [ ] **Step 3: Create `TrafficChartWidget`**

Queries `AccessLog` grouped by day for the last 30 days, counting distinct IPs as visitors and total rows as views. Roles: `admin`, `super_admin`.

```php
<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Widgets;

use Capell\Admin\Filament\Widgets\CapellWidget;
use Capell\Blog\Data\Dashboard\TrafficChartData;
use Capell\Blog\Data\Dashboard\TrafficPointData;
use Capell\Core\Models\AccessLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class TrafficChartWidget extends CapellWidget
{
    protected static string $settingsKey = 'traffic_chart';

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected string $view = 'capell-blog::filament.widgets.traffic-chart';

    protected int | string | array $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return ['data' => $this->getData()];
    }

    private function getData(): TrafficChartData
    {
        $rows = AccessLog::query()
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as views'),
                DB::raw('COUNT(DISTINCT ip_address) as visitors'),
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $points = $rows->map(fn (object $row): TrafficPointData => new TrafficPointData(
            date: $row->date,
            views: (int) $row->views,
            visitors: (int) $row->visitors,
        ));

        return new TrafficChartData(
            totalViews: (int) $rows->sum('views'),
            totalVisitors: (int) $rows->sum('visitors'),
            points: TrafficPointData::collect($points, Collection::class),
        );
    }
}
```

- [ ] **Step 4: Create the Blade view**

`packages/blog/resources/views/filament/widgets/traffic-chart.blade.php`:

```blade
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">📊 Site traffic — views &amp; visitors</x-slot>

        <div class="space-y-4">
            <div class="flex gap-6 text-sm">
                <span class="font-medium text-gray-700 dark:text-gray-300">
                    {{ number_format($data->totalViews) }} views
                </span>
                <span class="font-medium text-gray-500 dark:text-gray-400">
                    {{ number_format($data->totalVisitors) }} visitors
                </span>
            </div>

            @if ($data->points->isEmpty())
                <p class="text-sm text-gray-500">No traffic data yet.</p>
            @else
                {{-- Simple bar chart using inline SVG / CSS bars --}}
                <div class="flex h-32 items-end gap-1">
                    @foreach ($data->points as $point)
                        @php
                            $maxViews = $data->points->max('views') ?: 1;
                            $viewsHeight = round(($point->views / $maxViews) * 100);
                            $visitorsHeight = round(($point->visitors / $maxViews) * 100);
                        @endphp

                        <div
                            class="flex flex-1 flex-col items-center gap-0.5"
                            title="{{ $point->date }}: {{ $point->views }} views, {{ $point->visitors }} visitors"
                        >
                            <div class="flex h-28 w-full items-end gap-px">
                                <div
                                    class="flex-1 rounded-t bg-amber-400 dark:bg-amber-500"
                                    style="height: {{ $viewsHeight }}%"
                                ></div>
                                <div
                                    class="flex-1 rounded-t bg-blue-400 dark:bg-blue-500"
                                    style="height: {{ $visitorsHeight }}%"
                                ></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="flex gap-4 text-xs text-gray-500">
                    <span class="flex items-center gap-1.5">
                        <span
                            class="inline-block h-3 w-3 rounded-sm bg-amber-400"
                        ></span>
                        Views
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span
                            class="inline-block h-3 w-3 rounded-sm bg-blue-400"
                        ></span>
                        Visitors
                    </span>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
```

- [ ] **Step 5: Run tests**

```bash
php vendor/bin/pest tests/src/Blog/Feature/Filament/Widgets/TrafficChartWidgetTest.php -v
```

Expected: PASS.

- [ ] **Step 6: Register widget in BlogServiceProvider**

Find the widget registration section in `packages/blog/src/Providers/BlogServiceProvider.php` and add `TrafficChartWidget::class`.

- [ ] **Step 7: Commit**

```bash
git add packages/blog/src/Filament/Widgets/TrafficChartWidget.php \
        packages/blog/src/Data/Dashboard/ \
        packages/blog/resources/views/filament/widgets/traffic-chart.blade.php \
        tests/src/Blog/Feature/Filament/Widgets/TrafficChartWidgetTest.php \
        packages/blog/src/Providers/BlogServiceProvider.php
git commit -m "feat(blog): add combined TrafficChartWidget"
```

---

## Task 3: TopPageData + TopPagesData

**Files:**

- Create: `packages/blog/src/Data/Dashboard/TopPageData.php`
- Create: `packages/blog/src/Data/Dashboard/TopPagesData.php`

- [ ] **Step 1: Create data classes**

`TopPageData`:

```php
<?php

declare(strict_types=1);

namespace Capell\Blog\Data\Dashboard;

use Spatie\LaravelData\Data;

final class TopPageData extends Data
{
    public function __construct(
        public readonly string $path,
        public readonly int $views,
    ) {}
}
```

`TopPagesData`:

```php
<?php

declare(strict_types=1);

namespace Capell\Blog\Data\Dashboard;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

final class TopPagesData extends Data
{
    /**
     * @param  Collection<int, TopPageData>  $pages
     */
    public function __construct(
        public readonly Collection $pages,
    ) {}
}
```

- [ ] **Step 2: Commit**

```bash
git add packages/blog/src/Data/Dashboard/TopPageData.php packages/blog/src/Data/Dashboard/TopPagesData.php
git commit -m "feat(blog): add TopPageData and TopPagesData"
```

---

## Task 4: TopPagesWidget + view

**Files:**

- Create: `packages/blog/src/Filament/Widgets/TopPagesWidget.php`
- Create: `packages/blog/resources/views/filament/widgets/top-pages.blade.php`
- Create: `tests/src/Blog/Feature/Filament/Widgets/TopPagesWidgetTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Capell\Blog\Filament\Widgets\TopPagesWidgetAbstract;
use Capell\Core\Models\AccessLog;
use Capell\Core\Models\Page;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('widget');

it('renders for an admin user', function (): void {
    test()->actingAsAdmin();
    livewire(TopPagesWidgetAbstract::class)->assertOk();
});

it('shows top pages when access logs exist', function (): void {
    test()->actingAsAdmin();

    $page = Page::factory()->create(['slug' => '/home']);
    AccessLog::factory()->count(5)->create(['url' => '/home', 'loggable_id' => $page->id]);

    livewire(TopPagesWidgetAbstract::class)
        ->assertOk()
        ->assertSee('/home');
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php vendor/bin/pest tests/src/Blog/Feature/Filament/Widgets/TopPagesWidgetTest.php -v
```

Expected: FAIL.

- [ ] **Step 3: Create `TopPagesWidget`**

```php
<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Widgets;

use Capell\Admin\Filament\Widgets\CapellWidget;
use Capell\Blog\Data\Dashboard\TopPageData;
use Capell\Blog\Data\Dashboard\TopPagesData;
use Capell\Core\Models\AccessLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class TopPagesWidget extends CapellWidget
{
    protected static string $settingsKey = 'top_pages';

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected string $view = 'capell-blog::filament.widgets.top-pages';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return ['data' => $this->getData()];
    }

    private function getData(): TopPagesData
    {
        $rows = AccessLog::query()
            ->select('url', DB::raw('COUNT(*) as views'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('url')
            ->orderByDesc('views')
            ->limit(5)
            ->get();

        $pages = $rows->map(fn (object $row): TopPageData => new TopPageData(
            path: $row->url,
            views: (int) $row->views,
        ));

        return new TopPagesData(
            pages: TopPageData::collect($pages, Collection::class),
        );
    }
}
```

- [ ] **Step 4: Create the Blade view**

`packages/blog/resources/views/filament/widgets/top-pages.blade.php`:

```blade
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">🏆 Top pages</x-slot>

        @if ($data->pages->isEmpty())
            <p class="text-sm text-gray-500">No page views recorded yet.</p>
        @else
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($data->pages as $page)
                    <div class="flex items-center justify-between py-2 text-sm">
                        <span
                            class="truncate font-medium text-gray-700 dark:text-gray-300"
                        >
                            {{ $page->path }}
                        </span>
                        <span
                            class="ml-4 shrink-0 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400"
                        >
                            {{ number_format($page->views) }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
```

- [ ] **Step 5: Run tests**

```bash
php vendor/bin/pest tests/src/Blog/Feature/Filament/Widgets/TopPagesWidgetTest.php -v
```

Expected: PASS.

- [ ] **Step 6: Register widget in BlogServiceProvider**

Add `TopPagesWidget::class` to the widget registration alongside `TrafficChartWidget`.

- [ ] **Step 7: Commit**

```bash
git add packages/blog/src/Filament/Widgets/TopPagesWidget.php \
        packages/blog/src/Data/Dashboard/ \
        packages/blog/resources/views/filament/widgets/top-pages.blade.php \
        tests/src/Blog/Feature/Filament/Widgets/TopPagesWidgetTest.php \
        packages/blog/src/Providers/BlogServiceProvider.php
git commit -m "feat(blog): add TopPagesWidget"
```

---

## Task 5: ActivityItemData + RecentActivityData

**Files:**

- Create: `packages/mosaic/src/Data/Dashboard/ActivityItemData.php`
- Create: `packages/mosaic/src/Data/Dashboard/RecentActivityData.php`

- [ ] **Step 1: Create data classes**

`ActivityItemData`:

```php
<?php

declare(strict_types=1);

namespace Capell\Mosaic\Data\Dashboard;

use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

final class ActivityItemData extends Data
{
    public function __construct(
        public readonly string $title,
        public readonly string $type,
        public readonly string $status,
        public readonly CarbonInterface $updatedAt,
    ) {}
}
```

`RecentActivityData`:

```php
<?php

declare(strict_types=1);

namespace Capell\Mosaic\Data\Dashboard;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

final class RecentActivityData extends Data
{
    /**
     * @param  Collection<int, ActivityItemData>  $items
     */
    public function __construct(
        public readonly Collection $items,
    ) {}
}
```

- [ ] **Step 2: Commit**

```bash
git add packages/mosaic/src/Data/Dashboard/ActivityItemData.php packages/mosaic/src/Data/Dashboard/RecentActivityData.php
git commit -m "feat(mosaic): add ActivityItemData and RecentActivityData"
```

---

## Task 6: RecentActivityWidget + view

The widget queries the core `Page` model (and optionally `Article` from blog if installed) for recently modified content. It determines status from publish dates.

**Files:**

- Create: `packages/mosaic/src/Filament/Widgets/RecentActivityWidget.php`
- Create: `packages/mosaic/resources/views/filament/widgets/recent-activity.blade.php`
- Create: `tests/src/Mosaic/Feature/Filament/Widgets/RecentActivityWidgetTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Capell\Mosaic\Filament\Widgets\RecentActivityWidgetAbstract;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('widget');

it('renders for an admin user', function (): void {
    test()->actingAsAdmin();
    livewire(RecentActivityWidgetAbstract::class)->assertOk();
});

it('shows recent activity heading', function (): void {
    test()->actingAsAdmin();
    livewire(RecentActivityWidgetAbstract::class)
        ->assertOk()
        ->assertSee('Recent activity');
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php vendor/bin/pest tests/src/Mosaic/Feature/Filament/Widgets/RecentActivityWidgetTest.php -v
```

Expected: FAIL.

- [ ] **Step 3: Create `RecentActivityWidget`**

```php
<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Widgets;

use Capell\Admin\Filament\Widgets\CapellWidget;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Mosaic\Data\Dashboard\ActivityItemData;
use Capell\Mosaic\Data\Dashboard\RecentActivityData;
use Illuminate\Support\Collection;

final class RecentActivityWidget extends CapellWidget
{
    protected static string $settingsKey = 'recent_activity';

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected string $view = 'capell-mosaic::filament.widgets.recent-activity';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return ['data' => $this->getData()];
    }

    private function getData(): RecentActivityData
    {
        /** @var class-string<Page> $pageModel */
        $pageModel = CapellCore::getModel(\Capell\Core\Enums\ModelEnum::Page);

        $pages = $pageModel::query()
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        $items = $pages->map(function (Page $page): ActivityItemData {
            $status = $this->resolveStatus($page);

            return new ActivityItemData(
                title: $page->title ?? $page->slug,
                type: 'page',
                status: $status,
                updatedAt: $page->updated_at,
            );
        });

        return new RecentActivityData(
            items: ActivityItemData::collect($items, Collection::class),
        );
    }

    private function resolveStatus(Page $page): string
    {
        if ($page->visible_from === null) {
            return 'draft';
        }

        if ($page->visible_from > now()) {
            return 'scheduled';
        }

        if ($page->visible_until !== null && $page->visible_until <= now()) {
            return 'expired';
        }

        return 'published';
    }
}
```

- [ ] **Step 4: Create the Blade view**

`packages/mosaic/resources/views/filament/widgets/recent-activity.blade.php`:

```blade
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">🕐 Recent activity</x-slot>

        @if ($data->items->isEmpty())
            <p class="text-sm text-gray-500">No recent activity.</p>
        @else
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($data->items as $item)
                    @php
                        $badgeClass = match ($item->status) {
                            'published' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                            'draft' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                            'scheduled' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                            default => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                        };
                    @endphp

                    <div class="flex items-center justify-between py-2 text-sm">
                        <span
                            class="truncate font-medium text-gray-700 dark:text-gray-300"
                        >
                            {{ $item->title }}
                        </span>
                        <span
                            class="{{ $badgeClass }} ml-4 shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                        >
                            {{ $item->status }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
```

- [ ] **Step 5: Run tests**

```bash
php vendor/bin/pest tests/src/Mosaic/Feature/Filament/Widgets/RecentActivityWidgetTest.php -v
```

Expected: PASS.

- [ ] **Step 6: Register widget in MosaicServiceProvider**

Add `RecentActivityWidget::class` to the widget registration in `packages/mosaic/src/Providers/MosaicServiceProvider.php`.

- [ ] **Step 7: Commit**

```bash
git add packages/mosaic/src/Filament/Widgets/RecentActivityWidget.php \
        packages/mosaic/src/Data/Dashboard/ \
        packages/mosaic/resources/views/filament/widgets/recent-activity.blade.php \
        tests/src/Mosaic/Feature/Filament/Widgets/RecentActivityWidgetTest.php \
        packages/mosaic/src/Providers/MosaicServiceProvider.php
git commit -m "feat(mosaic): add RecentActivityWidget"
```

---

## Task 7: Full test suite + preflight

- [ ] **Step 1: Run all tests**

```bash
composer test
```

Expected: all pass.

- [ ] **Step 2: Run preflight**

```bash
composer preflight
```

Fix any Pint/PHPStan/Rector issues reported.

- [ ] **Step 3: Final commit if preflight made changes**

```bash
git add -p
git commit -m "chore: apply preflight fixes"
```

---

## Out of scope (core admin — separate plan needed)

The following changes shown in the mockup require work in `capell-app/capell`, not here:

- Date filter as pill tabs (replace "Date range" dropdown on the dashboard)
- StatsOverview trend sparklines + "↑ X% vs prior period" text
- CacheHealthWidget moved to a "Developer dashboard" sub-page with header button
- AlertsWidget + SetupHealthWidget + ContentHealthWidget merged into one combined health widget
