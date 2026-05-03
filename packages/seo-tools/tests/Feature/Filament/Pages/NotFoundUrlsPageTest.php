<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\PageView;
use Capell\Core\Models\Site;
use Capell\SeoTools\Filament\Pages\NotFoundUrlsPage;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Testing\TestAction;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection as SupportCollection;

use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class)
    ->group('not-found-urls');

function createScopedUserForNotFoundUrlsPageTest(SupportCollection $assignedSiteIds): Authenticatable
{
    $user = new class extends Authenticatable implements FilamentUser
    {
        use HasFactory;

        /** @var SupportCollection<int, int> */
        public SupportCollection $assignedSiteIds;

        protected $table = 'users';

        public function canAccessPanel(Panel $panel): bool
        {
            return true;
        }

        /** @return SupportCollection<int, int> */
        public function getAssignedSiteIds(): SupportCollection
        {
            return $this->assignedSiteIds;
        }

        public function isGlobalAdmin(): bool
        {
            return false;
        }
    };

    $user->forceFill([
        'name' => 'Scoped Not Found User',
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password'),
    ]);
    $user->assignedSiteIds = $assignedSiteIds;

    return $user;
}

beforeEach(function (): void {
    Permission::query()->firstOrCreate(['name' => 'View:NotFoundUrlsPage', 'guard_name' => 'web']);

    test()->actingAsAdmin();
});

test('query limits not found urls to assigned sites for non-global users', function (): void {
    $assignedSite = Site::factory()->withTranslations()->create();
    $hiddenSite = Site::factory()->withTranslations()->create();
    $missingPageType = resolve(Page::class)->getMorphClass();

    PageView::factory()->create([
        'site_id' => $assignedSite->id,
        'url' => '/assigned-missing',
        'pageable_type' => $missingPageType,
        'pageable_id' => 1001,
    ]);

    PageView::factory()->create([
        'site_id' => $hiddenSite->id,
        'url' => '/hidden-missing',
        'pageable_type' => $missingPageType,
        'pageable_id' => 1002,
    ]);

    test()->actingAs(createScopedUserForNotFoundUrlsPageTest(collect([$assignedSite->getKey()])));

    expect(NotFoundUrlsPage::getEloquentQuery()->pluck('url')->all())
        ->toBe(['/assigned-missing']);
});

test('query denies not found urls for non-global users without assigned sites', function (): void {
    PageView::factory()->create([
        'url' => '/hidden-missing',
        'pageable_type' => resolve(Page::class)->getMorphClass(),
        'pageable_id' => 1001,
    ]);

    test()->actingAs(createScopedUserForNotFoundUrlsPageTest(collect()));

    expect(NotFoundUrlsPage::getEloquentQuery()->get())->toBeEmpty();
});

it('can not render not found urls page without permission', function (): void {
    test()->actingAsUser();

    get(NotFoundUrlsPage::getUrl())
        ->assertForbidden();
});

test('can sort not found urls by total visitors and last viewed at', function (): void {
    auth()->user()->givePermissionTo('View:NotFoundUrlsPage');

    $missingPageType = resolve(Page::class)->getMorphClass();

    $sortRecordC1 = PageView::factory()->create([
        'url' => '/missing/sort-c',
        'session_id' => 'visitor-1',
        'pageable_type' => $missingPageType,
        'pageable_id' => 11,
        'viewed_at' => now(),
    ]);

    $sortRecordC2 = PageView::factory()->create([
        'url' => '/missing/sort-c',
        'session_id' => 'visitor-2',
        'pageable_type' => $missingPageType,
        'pageable_id' => 11,
        'viewed_at' => now(),
    ]);

    $sortRecordC3 = PageView::factory()->create([
        'url' => '/missing/sort-c',
        'session_id' => 'visitor-3',
        'pageable_type' => $missingPageType,
        'pageable_id' => 11,
        'viewed_at' => now(),
    ]);

    $sortRecordB1 = PageView::factory()->create([
        'url' => '/missing/sort-b',
        'session_id' => 'visitor-4',
        'pageable_type' => $missingPageType,
        'pageable_id' => 22,
        'viewed_at' => now(),
    ]);

    $sortRecordB2 = PageView::factory()->create([
        'url' => '/missing/sort-b',
        'session_id' => 'visitor-5',
        'pageable_type' => $missingPageType,
        'pageable_id' => 22,
        'viewed_at' => now(),
    ]);

    $sortRecordA1 = PageView::factory()->create([
        'url' => '/missing/sort-a',
        'session_id' => 'visitor-6',
        'pageable_type' => $missingPageType,
        'pageable_id' => 33,
        'viewed_at' => now(),
    ]);

    $sortRecordC1->update(['viewed_at' => now()->subMinutes(30)]);
    $sortRecordC2->update(['viewed_at' => now()->subMinutes(25)]);
    $sortRecordC3->update(['viewed_at' => now()->subMinutes(5)]);
    $sortRecordB1->update(['viewed_at' => now()->subMinutes(20)]);
    $sortRecordB2->update(['viewed_at' => now()->subMinutes(10)]);
    $sortRecordA1->update(['viewed_at' => now()->subMinutes(15)]);

    $sortedByTotalVisitors = PageView::query()
        ->notFound()
        ->selectRaw('url, MAX(viewed_at) as last_viewed_at, COUNT(DISTINCT session_id) as total_visitors')
        ->groupBy('url')
        ->orderBy('total_visitors')
        ->get();

    livewire(NotFoundUrlsPage::class)
        ->assertSuccessful()
        ->sortTable('total_visitors')
        ->assertCanSeeTableRecords($sortedByTotalVisitors, inOrder: true);

    $sortedByLastViewedAt = PageView::query()
        ->notFound()
        ->selectRaw('url, MAX(viewed_at) as last_viewed_at, COUNT(DISTINCT session_id) as total_visitors')
        ->groupBy('url')
        ->oldest('last_viewed_at')
        ->get();

    livewire(NotFoundUrlsPage::class)
        ->assertSuccessful()
        ->sortTable('last_viewed_at')
        ->assertCanSeeTableRecords($sortedByLastViewedAt, inOrder: true);
});

test('can search not found urls by url', function (): void {
    auth()->user()->givePermissionTo('View:NotFoundUrlsPage');

    $missingPageType = resolve(Page::class)->getMorphClass();

    PageView::factory()->create([
        'url' => '/missing/search-target',
        'session_id' => 'session-search-1',
        'pageable_type' => $missingPageType,
        'pageable_id' => 44,
    ]);

    PageView::factory()->create([
        'url' => '/missing/other-url',
        'session_id' => 'session-search-2',
        'pageable_type' => $missingPageType,
        'pageable_id' => 55,
    ]);

    $matchingRecord = PageView::query()
        ->notFound()
        ->selectRaw('url, MAX(viewed_at) as last_viewed_at, COUNT(DISTINCT session_id) as total_visitors')
        ->where('url', '/missing/search-target')
        ->groupBy('url')
        ->firstOrFail();

    $otherRecord = PageView::query()
        ->notFound()
        ->selectRaw('url, MAX(viewed_at) as last_viewed_at, COUNT(DISTINCT session_id) as total_visitors')
        ->where('url', '/missing/other-url')
        ->groupBy('url')
        ->firstOrFail();

    livewire(NotFoundUrlsPage::class)
        ->assertSuccessful()
        ->assertCountTableRecords(2)
        ->searchTable('search-target')
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([$matchingRecord])
        ->assertCanNotSeeTableRecords([$otherRecord]);
});

test('escapes logged not found urls before rendering links', function (): void {
    auth()->user()->givePermissionTo('View:NotFoundUrlsPage');

    PageView::factory()->create([
        'url' => "/missing/'><script>alert(1)</script>",
        'session_id' => 'session-xss',
        'pageable_type' => resolve(Page::class)->getMorphClass(),
        'pageable_id' => 99,
    ]);

    livewire(NotFoundUrlsPage::class)
        ->assertSuccessful()
        ->assertDontSeeHtml('<script>alert(1)</script>')
        ->assertSeeHtml('&lt;script&gt;alert(1)&lt;/script&gt;');
});

test('does not render unsafe logged not found urls as links', function (): void {
    auth()->user()->givePermissionTo('View:NotFoundUrlsPage');

    PageView::factory()->create([
        'url' => 'javascript:alert(1)',
        'session_id' => 'session-unsafe-link',
        'pageable_type' => resolve(Page::class)->getMorphClass(),
        'pageable_id' => 99,
    ]);

    livewire(NotFoundUrlsPage::class)
        ->assertSuccessful()
        ->assertSee('javascript:alert(1)')
        ->assertDontSeeHtml('href="javascript:alert(1)"');
});

test('can bulk delete selected not found urls', function (): void {
    auth()->user()->givePermissionTo('View:NotFoundUrlsPage');

    $missingPageType = resolve(Page::class)->getMorphClass();

    PageView::factory()->create([
        'url' => '/missing/delete-first',
        'session_id' => 'session-delete-1-a',
        'pageable_type' => $missingPageType,
        'pageable_id' => 66,
    ]);

    PageView::factory()->create([
        'url' => '/missing/delete-first',
        'session_id' => 'session-delete-1-b',
        'pageable_type' => $missingPageType,
        'pageable_id' => 66,
    ]);

    PageView::factory()->create([
        'url' => '/missing/delete-second',
        'session_id' => 'session-delete-2-a',
        'pageable_type' => $missingPageType,
        'pageable_id' => 77,
    ]);

    PageView::factory()->create([
        'url' => '/missing/delete-second',
        'session_id' => 'session-delete-2-b',
        'pageable_type' => $missingPageType,
        'pageable_id' => 77,
    ]);

    PageView::factory()->create([
        'url' => '/missing/keep-me',
        'session_id' => 'session-delete-3',
        'pageable_type' => $missingPageType,
        'pageable_id' => 88,
    ]);

    livewire(NotFoundUrlsPage::class)
        ->assertSuccessful()
        ->assertCountTableRecords(3)
        ->selectTableRecords(['/missing/delete-first', '/missing/delete-second'])
        ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(1);

    expect(PageView::query()->where('url', '/missing/delete-first')->exists())->toBeFalse();
    expect(PageView::query()->where('url', '/missing/delete-second')->exists())->toBeFalse();
    expect(PageView::query()->where('url', '/missing/keep-me')->exists())->toBeTrue();
});
