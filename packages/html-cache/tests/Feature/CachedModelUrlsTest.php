<?php

declare(strict_types=1);

use Capell\Admin\Data\Bridges\AdminBridgeContextData;
use Capell\Admin\Enums\AdminSurfaceContributionType;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Pages\SiteHealthPage;
use Capell\Admin\Support\Bridges\AdminBridgeRegistrar;
use Capell\Core\Models\Concerns\HasSitePermissions;
use Capell\Core\Models\Page;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Translation;
use Capell\HtmlCache\Actions\BuildCachedModelUrlDiagnosticsAction;
use Capell\HtmlCache\Actions\BuildCacheMapOverviewAction;
use Capell\HtmlCache\Actions\BuildHtmlCachePublicOutputSafetyDiagnosticsAction;
use Capell\HtmlCache\Actions\ClearCachedUrlAction;
use Capell\HtmlCache\Actions\EnsureHtmlCachePermissionsAction;
use Capell\HtmlCache\Actions\ListCacheMapResourceOptionsAction;
use Capell\HtmlCache\Actions\RecordCachedModelUrlsAction;
use Capell\HtmlCache\Bridges\HtmlCacheAdminBridge;
use Capell\HtmlCache\Enums\HtmlCachePermission;
use Capell\HtmlCache\Filament\Resources\CachedModelUrls\CachedModelUrlResource;
use Capell\HtmlCache\Jobs\RegisterCachedModelUrlsJob;
use Capell\HtmlCache\Livewire\SiteHealthCacheMap;
use Capell\HtmlCache\Models\CachedModelUrl;
use Capell\HtmlCache\Support\Admin\HtmlCacheSiteHealthWidget;
use Capell\HtmlCache\Support\Cache\HtmlCachePathResolver;
use Capell\HtmlCache\Support\Cache\HtmlCacheStore;
use Capell\HtmlCache\Tests\HtmlCacheTestCase;
use Capell\Tests\Fixtures\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

use function Pest\Livewire\livewire;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(HtmlCacheTestCase::class);

function htmlCacheMapTestComponent(int $siteId, string $modelType): mixed
{
    return livewire(SiteHealthCacheMap::class, ['siteId' => $siteId])
        ->set('selectedModelType', $modelType);
}

it('records rendered models against a cached url and removes stale model links', function (): void {
    [$siteDomain, $page] = EloquentModel::withoutEvents(function (): array {
        $siteDomain = SiteDomain::factory()->create([
            'scheme' => 'https',
            'domain' => 'example.test',
            'path' => null,
        ]);

        return [
            $siteDomain,
            Page::factory()
                ->recycle($siteDomain->site)
                ->withTranslations()
                ->create(),
        ];
    });
    $translation = $page->translations()->where('language_id', $siteDomain->language_id)->first();

    expect($translation)->toBeInstanceOf(Translation::class);
    $url = 'https://example.test/about';

    RecordCachedModelUrlsAction::run($url, [
        $page->getMorphClass() => [$page->getKey()],
        $translation->getMorphClass() => [$translation->getKey()],
    ]);

    expect(CachedModelUrl::query()->where('url', $url)->count())->toBe(2)
        ->and(CachedModelUrl::query()->where('url', $url)->first())
        ->site_id->toBe($siteDomain->site_id)
        ->site_domain_id->toBe($siteDomain->getKey())
        ->language_id->toBe($siteDomain->language_id)
        ->path->toBe('/about');

    RecordCachedModelUrlsAction::run($url, [
        $page->getMorphClass() => [$page->getKey()],
    ]);

    expect(CachedModelUrl::query()->where('url', $url)->count())->toBe(1)
        ->and(CachedModelUrl::query()->where('url', $url)->first())
        ->cacheable_type->toBe($page->getMorphClass())
        ->cacheable_id->toBe($page->getKey());
});

it('clears cached files and table rows for a url', function (): void {
    Storage::fake('page_cache');

    $siteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'example.test',
        'path' => null,
    ]);
    $page = Page::factory()
        ->recycle($siteDomain->site)
        ->withTranslations()
        ->create();
    $url = 'https://example.test/about';
    $cachePath = resolve(HtmlCachePathResolver::class)->pathForUrl('/about', $siteDomain);
    $errorCachePath = resolve(HtmlCachePathResolver::class)->pathForUrl('/about', $siteDomain, error: true);

    Storage::disk('page_cache')->put($cachePath, 'cached page');
    Storage::disk('page_cache')->put($errorCachePath, 'cached error page');
    CachedModelUrl::query()->create([
        'url' => $url,
        'url_hash' => CachedModelUrl::hashUrl($url),
        'path' => '/about',
        'site_id' => $siteDomain->site_id,
        'site_domain_id' => $siteDomain->getKey(),
        'language_id' => $siteDomain->language_id,
        'cacheable_type' => $page->getMorphClass(),
        'cacheable_id' => $page->getKey(),
        'cached_at' => now(),
        'last_seen_at' => now(),
    ]);

    expect(ClearCachedUrlAction::run($url))->toBeTrue()
        ->and(Storage::disk('page_cache')->exists($cachePath))->toBeFalse()
        ->and(Storage::disk('page_cache')->exists($errorCachePath))->toBeFalse()
        ->and(CachedModelUrl::query()->where('url', $url)->exists())->toBeFalse();
});

it('clears only the selected site scope when clearing a cached model url row', function (): void {
    Storage::fake('page_cache');

    $firstSiteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'first.test',
        'path' => null,
    ]);
    $secondSiteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'second.test',
        'path' => null,
    ]);
    $firstPage = Page::factory()
        ->recycle($firstSiteDomain->site)
        ->withTranslations()
        ->create();
    $secondPage = Page::factory()
        ->recycle($secondSiteDomain->site)
        ->withTranslations()
        ->create();
    $url = 'https://shared.test/about';
    $firstCachePath = resolve(HtmlCachePathResolver::class)->pathForUrl('/about', $firstSiteDomain);
    $secondCachePath = resolve(HtmlCachePathResolver::class)->pathForUrl('/about', $secondSiteDomain);

    Storage::disk('page_cache')->put($firstCachePath, 'first cached page');
    Storage::disk('page_cache')->put($secondCachePath, 'second cached page');

    $firstCachedModelUrl = CachedModelUrl::query()->create([
        'url' => $url,
        'url_hash' => CachedModelUrl::hashUrl($url),
        'path' => '/about',
        'site_id' => $firstSiteDomain->site_id,
        'site_domain_id' => $firstSiteDomain->getKey(),
        'language_id' => $firstSiteDomain->language_id,
        'cacheable_type' => $firstPage->getMorphClass(),
        'cacheable_id' => $firstPage->getKey(),
        'cached_at' => now(),
        'last_seen_at' => now(),
    ]);
    $secondCachedModelUrl = CachedModelUrl::query()->create([
        'url' => $url,
        'url_hash' => CachedModelUrl::hashUrl($url),
        'path' => '/about',
        'site_id' => $secondSiteDomain->site_id,
        'site_domain_id' => $secondSiteDomain->getKey(),
        'language_id' => $secondSiteDomain->language_id,
        'cacheable_type' => $secondPage->getMorphClass(),
        'cacheable_id' => $secondPage->getKey(),
        'cached_at' => now(),
        'last_seen_at' => now(),
    ]);

    expect(ClearCachedUrlAction::run($firstCachedModelUrl))->toBeTrue()
        ->and(Storage::disk('page_cache')->exists($firstCachePath))->toBeFalse()
        ->and(Storage::disk('page_cache')->exists($secondCachePath))->toBeTrue()
        ->and(CachedModelUrl::query()->whereKey($firstCachedModelUrl->getKey())->exists())->toBeFalse()
        ->and(CachedModelUrl::query()->whereKey($secondCachedModelUrl->getKey())->exists())->toBeTrue();
});

it('registers html cache middleware into the real frontend route middleware stack', function (): void {
    $route = Route::getRoutes()->getByName('capell-frontend.page');

    expect($route)->not->toBeNull();

    $middleware = $route->gatherMiddleware();

    expect($middleware)
        ->toContain('frontend.cache')
        ->toContain('frontend.model_events')
        ->toContain('frontend.no_session_cookies_on_cache')
        ->and(array_search('frontend.cache', $middleware, true))
        ->toBeGreaterThan(array_search('web', $middleware, true))
        ->and(array_search('frontend.cache', $middleware, true))
        ->toBeLessThan(array_search('frontend.resolve', $middleware, true))
        ->and(array_search('frontend.model_events', $middleware, true))
        ->toBeGreaterThan(array_search('frontend.anonymous_cacheable_render', $middleware, true));
});

it('clears stale cached url rows when the url no longer resolves to a site domain', function (): void {
    $siteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'example.test',
        'path' => null,
    ]);
    $page = Page::factory()
        ->recycle($siteDomain->site)
        ->withTranslations()
        ->create();
    $url = 'https://old-domain.test/about';

    CachedModelUrl::query()->create([
        'url' => $url,
        'url_hash' => CachedModelUrl::hashUrl($url),
        'path' => '/about',
        'site_id' => $siteDomain->site_id,
        'site_domain_id' => $siteDomain->getKey(),
        'language_id' => $siteDomain->language_id,
        'cacheable_type' => $page->getMorphClass(),
        'cacheable_id' => $page->getKey(),
        'cached_at' => now(),
        'last_seen_at' => now(),
    ]);

    expect(ClearCachedUrlAction::run($url))->toBeFalse()
        ->and(CachedModelUrl::query()->where('url', $url)->exists())->toBeFalse();
});

it('clears historical cached files from stored rows when the url no longer resolves', function (): void {
    Storage::fake('page_cache');

    $siteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'old-domain.test',
        'path' => null,
    ]);
    $page = Page::factory()
        ->recycle($siteDomain->site)
        ->withTranslations()
        ->create();
    $url = 'https://missing-domain.test/about';
    $cachePath = resolve(HtmlCachePathResolver::class)->pathForUrl('/about', $siteDomain);

    Storage::disk('page_cache')->put($cachePath, 'stale cached page');
    CachedModelUrl::query()->create([
        'url' => $url,
        'url_hash' => CachedModelUrl::hashUrl($url),
        'path' => '/about',
        'site_id' => $siteDomain->site_id,
        'site_domain_id' => $siteDomain->getKey(),
        'language_id' => $siteDomain->language_id,
        'cacheable_type' => $page->getMorphClass(),
        'cacheable_id' => $page->getKey(),
        'cached_at' => now(),
        'last_seen_at' => now(),
    ]);

    expect(ClearCachedUrlAction::run($url))->toBeFalse()
        ->and(Storage::disk('page_cache')->exists($cachePath))->toBeFalse()
        ->and(CachedModelUrl::query()->where('url', $url)->exists())->toBeFalse();
});

it('clears all cached urls when a site domain changes', function (): void {
    Storage::fake('page_cache');

    $siteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'example.test',
        'path' => null,
    ]);
    $page = Page::factory()
        ->recycle($siteDomain->site)
        ->withTranslations()
        ->create();
    $url = 'https://example.test/about';
    $cachePath = resolve(HtmlCachePathResolver::class)->pathForUrl('/about', $siteDomain);

    Storage::disk('page_cache')->put($cachePath, 'cached page');
    CachedModelUrl::query()->create([
        'url' => $url,
        'url_hash' => CachedModelUrl::hashUrl($url),
        'path' => '/about',
        'site_id' => $siteDomain->site_id,
        'site_domain_id' => $siteDomain->getKey(),
        'language_id' => $siteDomain->language_id,
        'cacheable_type' => $page->getMorphClass(),
        'cacheable_id' => $page->getKey(),
        'cached_at' => now(),
        'last_seen_at' => now(),
    ]);

    $siteDomain->update(['domain' => 'new-example.test']);
    app()->terminate();

    expect(Storage::disk('page_cache')->exists($cachePath))->toBeFalse()
        ->and(CachedModelUrl::query()->where('url', $url)->exists())->toBeFalse();
});

it('reports unsafe cached public html through package diagnostics', function (): void {
    Storage::fake('page_cache');
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    test()->actingAs($user);

    $siteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'example.test',
        'path' => null,
    ]);
    $page = Page::factory()
        ->recycle($siteDomain->site)
        ->withTranslations()
        ->create();
    $url = 'https://example.test/about';
    $cachePath = resolve(HtmlCachePathResolver::class)->pathForUrl('/about', $siteDomain);

    Storage::disk('page_cache')->put($cachePath, '<div data-capell-editor="1"></div>');
    CachedModelUrl::query()->create([
        'url' => $url,
        'url_hash' => CachedModelUrl::hashUrl($url),
        'path' => '/about',
        'site_id' => $siteDomain->site_id,
        'site_domain_id' => $siteDomain->getKey(),
        'language_id' => $siteDomain->language_id,
        'cacheable_type' => $page->getMorphClass(),
        'cacheable_id' => $page->getKey(),
        'cached_at' => now(),
        'last_seen_at' => now(),
    ]);

    $checks = BuildHtmlCachePublicOutputSafetyDiagnosticsAction::run();

    expect($checks)->toHaveCount(1)
        ->and($checks[0]->status)->toBe('red')
        ->and($checks[0]->detail)->toContain('data-capell-editor');
});

it('scopes cached public html diagnostics to the selected site', function (): void {
    Storage::fake('page_cache');
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    test()->actingAs($user);

    $firstSiteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'first.test',
        'path' => null,
    ]);
    $secondSiteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'second.test',
        'path' => null,
    ]);
    $firstPage = Page::factory()
        ->recycle($firstSiteDomain->site)
        ->withTranslations()
        ->create();
    $secondPage = Page::factory()
        ->recycle($secondSiteDomain->site)
        ->withTranslations()
        ->create();

    Storage::disk('page_cache')->put(
        resolve(HtmlCachePathResolver::class)->pathForUrl('/about', $firstSiteDomain),
        '<main>safe</main>',
    );
    Storage::disk('page_cache')->put(
        resolve(HtmlCachePathResolver::class)->pathForUrl('/about', $secondSiteDomain),
        '<div data-capell-editor="1"></div>',
    );

    foreach ([[$firstSiteDomain, $firstPage], [$secondSiteDomain, $secondPage]] as [$siteDomain, $page]) {
        CachedModelUrl::query()->create([
            'url' => sprintf('https://%s/about', $siteDomain->domain),
            'url_hash' => CachedModelUrl::hashUrl(sprintf('https://%s/about', $siteDomain->domain)),
            'path' => '/about',
            'site_id' => $siteDomain->site_id,
            'site_domain_id' => $siteDomain->getKey(),
            'language_id' => $siteDomain->language_id,
            'cacheable_type' => $page->getMorphClass(),
            'cacheable_id' => $page->getKey(),
            'cached_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    $checks = BuildHtmlCachePublicOutputSafetyDiagnosticsAction::run((int) $firstSiteDomain->site_id);

    expect($checks)->toHaveCount(1)
        ->and($checks[0]->status)->toBe('green');
});

it('does not inspect path-based cached public html outside the selected site', function (): void {
    Storage::fake('page_cache');
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    test()->actingAs($user);

    $firstSiteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'example.test',
        'path' => '/uk',
    ]);
    $secondSiteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'example.test',
        'path' => '/fr',
    ]);

    Storage::disk('page_cache')->put(
        resolve(HtmlCachePathResolver::class)->pathForUrl('/', $firstSiteDomain),
        '<main>safe</main>',
    );
    Storage::disk('page_cache')->put(
        resolve(HtmlCachePathResolver::class)->pathForUrl('/', $secondSiteDomain),
        '<div data-capell-editor="1"></div>',
    );

    $checks = BuildHtmlCachePublicOutputSafetyDiagnosticsAction::run((int) $firstSiteDomain->site_id);

    expect(collect($checks)->pluck('status')->all())->not->toContain('red')
        ->and(collect($checks)->pluck('detail')->implode(' '))->not->toContain('data-capell-editor');
});

it('installs html cache permissions', function (): void {
    EnsureHtmlCachePermissionsAction::run();

    expect(Permission::query()
        ->whereIn('name', HtmlCachePermission::names())
        ->count())->toBe(count(HtmlCachePermission::cases()));
});

it('reports unsafe unindexed cached public html files', function (): void {
    Storage::fake('page_cache');
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    test()->actingAs($user);

    $siteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'example.test',
        'path' => null,
    ]);
    $orphanCachePath = resolve(HtmlCachePathResolver::class)->pathForUrl('/orphan', $siteDomain);

    Storage::disk('page_cache')->put($orphanCachePath, '<div data-capell-editor="1"></div>');

    $checks = BuildHtmlCachePublicOutputSafetyDiagnosticsAction::run((int) $siteDomain->site_id);

    expect(collect($checks)->pluck('status')->all())->toContain('amber', 'red')
        ->and(collect($checks)->pluck('detail')->implode(' '))->toContain('without cache index rows')
        ->and(collect($checks)->pluck('detail')->implode(' '))->toContain('data-capell-editor');
});

it('reports cached model url diagnostics for the selected site only', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    test()->actingAs($user);

    $firstSiteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'first.test',
        'path' => null,
    ]);
    $secondSiteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'second.test',
        'path' => null,
    ]);
    $firstPage = Page::factory()
        ->recycle($firstSiteDomain->site)
        ->withTranslations()
        ->create();
    $secondPage = Page::factory()
        ->recycle($secondSiteDomain->site)
        ->withTranslations()
        ->create();

    CachedModelUrl::query()->create([
        'url' => 'https://first.test/about',
        'url_hash' => CachedModelUrl::hashUrl('https://first.test/about'),
        'path' => '/about',
        'site_id' => $firstSiteDomain->site_id,
        'site_domain_id' => $firstSiteDomain->getKey(),
        'language_id' => $firstSiteDomain->language_id,
        'cacheable_type' => $firstPage->getMorphClass(),
        'cacheable_id' => $firstPage->getKey(),
        'cached_at' => now(),
        'last_seen_at' => now(),
    ]);
    CachedModelUrl::query()->create([
        'url' => 'https://second.test/about',
        'url_hash' => CachedModelUrl::hashUrl('https://second.test/about'),
        'path' => '/about',
        'site_id' => $secondSiteDomain->site_id,
        'site_domain_id' => $secondSiteDomain->getKey(),
        'language_id' => $secondSiteDomain->language_id,
        'cacheable_type' => $secondPage->getMorphClass(),
        'cacheable_id' => $secondPage->getKey(),
        'cached_at' => now(),
        'last_seen_at' => now(),
    ]);

    $checks = BuildCachedModelUrlDiagnosticsAction::run((int) $firstSiteDomain->site_id);

    expect($checks)->toHaveCount(1)
        ->and($checks[0]->status)->toBe('green')
        ->and($checks[0]->detail)->toContain('1 of 1');
});

it('reports when no cached model urls are tracked', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    test()->actingAs($user);

    $checks = BuildCachedModelUrlDiagnosticsAction::run();

    expect($checks)->toHaveCount(1)
        ->and($checks[0]->status)->toBe('amber')
        ->and($checks[0]->detail)->toBe(__('capell-html-cache::admin.no_cached_model_urls_tracked'));
});

it('exposes the selected site and cache map widget on site health', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    test()->actingAs($user);

    $siteDomain = SiteDomain::factory()->create();
    $page = resolve(SiteHealthPage::class);

    $page->mount();

    expect($page->selectedSiteId)->toBe($siteDomain->site_id)
        ->and($page->siteOptions())->toHaveKey($siteDomain->site_id)
        ->and(collect($page->siteHealthWidgets())->map(fn (object $widget): string => $widget->key())->all())->toContain('html-cache-map')
        ->and(resolve(HtmlCacheSiteHealthWidget::class)->component())->toBe('capell-html-cache.site-health-cache-map')
        ->and(view()->exists('capell-html-cache::livewire.site-health-cache-map'))->toBeTrue();
});

it('does not register the cached model urls resource as an admin page', function (): void {
    CapellAdmin::clearAdminSurfaceContributions();

    (new HtmlCacheAdminBridge)->register(
        new AdminBridgeRegistrar,
        AdminBridgeContextData::forPackage('capell-app/html-cache'),
    );

    expect(CapellAdmin::getAdminSurfaceContributions(AdminSurfaceContributionType::Resource))->toBe([]);
});

it('builds a cache map overview grouped by model and top resource impact', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    test()->actingAs($user);

    $siteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'example.test',
        'path' => null,
    ]);
    $sharedPage = Page::factory()
        ->recycle($siteDomain->site)
        ->withTranslations()
        ->create(['name' => 'Shared page']);
    $secondaryPage = Page::factory()
        ->recycle($siteDomain->site)
        ->withTranslations()
        ->create(['name' => 'Secondary page']);
    $translation = $sharedPage->translations()->first();

    expect($translation)->toBeInstanceOf(Translation::class);

    foreach ([
        ['https://example.test/about', $sharedPage],
        ['https://example.test/team', $sharedPage],
        ['https://example.test/about', $secondaryPage],
        ['https://example.test/about', $translation],
    ] as [$url, $cacheable]) {
        CachedModelUrl::query()->create([
            'url' => $url,
            'url_hash' => CachedModelUrl::hashUrl($url),
            'path' => str_replace('https://example.test', '', $url),
            'site_id' => $siteDomain->site_id,
            'site_domain_id' => $siteDomain->getKey(),
            'language_id' => $siteDomain->language_id,
            'cacheable_type' => $cacheable->getMorphClass(),
            'cacheable_id' => $cacheable->getKey(),
            'cached_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    $overview = BuildCacheMapOverviewAction::run((int) $siteDomain->site_id);

    expect($overview->totalUrls)->toBe(2)
        ->and($overview->totalDependencies)->toBe(4)
        ->and($overview->modelSummaries[0]->modelType)->toBe($sharedPage->getMorphClass())
        ->and($overview->modelSummaries[0]->urlCount)->toBe(2)
        ->and($overview->modelSummaries[0]->dependencyCount)->toBe(3)
        ->and(str_starts_with($overview->topResources[0]->label, 'Shared page'))->toBeTrue()
        ->and($overview->topResources[0]->urlCount)->toBe(2);
});

it('lists the top five cache map resources for the selected model and search', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    test()->actingAs($user);

    $siteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'example.test',
        'path' => null,
    ]);
    $otherSiteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'other.test',
        'path' => null,
    ]);
    $pages = collect(range(1, 6))
        ->map(fn (int $index): Page => Page::factory()
            ->recycle($siteDomain->site)
            ->withTranslations()
            ->create(['name' => $index === 6 ? 'Needle page' : 'Page ' . $index]));
    $otherSitePage = Page::factory()
        ->recycle($otherSiteDomain->site)
        ->withTranslations()
        ->create(['name' => 'Other site page']);

    $pages->each(function (Page $page, int $zeroBasedIndex) use ($siteDomain): void {
        foreach (range(1, 6 - $zeroBasedIndex) as $urlIndex) {
            $url = sprintf('https://example.test/page-%s-%s', $page->getKey(), $urlIndex);

            CachedModelUrl::query()->create([
                'url' => $url,
                'url_hash' => CachedModelUrl::hashUrl($url),
                'path' => str_replace('https://example.test', '', $url),
                'site_id' => $siteDomain->site_id,
                'site_domain_id' => $siteDomain->getKey(),
                'language_id' => $siteDomain->language_id,
                'cacheable_type' => $page->getMorphClass(),
                'cacheable_id' => $page->getKey(),
                'cached_at' => now(),
                'last_seen_at' => now(),
            ]);
        }
    });

    CachedModelUrl::query()->create([
        'url' => 'https://other.test/page',
        'url_hash' => CachedModelUrl::hashUrl('https://other.test/page'),
        'path' => '/page',
        'site_id' => $otherSiteDomain->site_id,
        'site_domain_id' => $otherSiteDomain->getKey(),
        'language_id' => $otherSiteDomain->language_id,
        'cacheable_type' => $otherSitePage->getMorphClass(),
        'cacheable_id' => $otherSitePage->getKey(),
        'cached_at' => now(),
        'last_seen_at' => now(),
    ]);

    $topOptions = ListCacheMapResourceOptionsAction::run($pages->first()->getMorphClass(), (int) $siteDomain->site_id);
    $searchOptions = ListCacheMapResourceOptionsAction::run($pages->first()->getMorphClass(), (int) $siteDomain->site_id, 'Needle');

    expect($topOptions)->toHaveCount(5)
        ->and(collect($topOptions)->pluck('label')->all())->not->toContain('Other site page')
        ->and(str_starts_with((string) $topOptions[0]->label, 'Page 1'))->toBeTrue()
        ->and($searchOptions)->toHaveCount(1)
        ->and(str_starts_with((string) $searchOptions[0]->label, 'Needle page'))->toBeTrue();
});

it('clears cache map rows through the table action for authorized actors', function (): void {
    Storage::fake('page_cache');

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    test()->actingAs($user);

    $siteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'example.test',
        'path' => null,
    ]);
    $page = Page::factory()
        ->recycle($siteDomain->site)
        ->withTranslations()
        ->create();
    $url = 'https://example.test/about';
    $cachePath = resolve(HtmlCachePathResolver::class)->pathForUrl('/about', $siteDomain);

    Storage::disk('page_cache')->put($cachePath, 'cached page');
    $cachedModelUrl = CachedModelUrl::query()->create([
        'url' => $url,
        'url_hash' => CachedModelUrl::hashUrl($url),
        'path' => '/about',
        'site_id' => $siteDomain->site_id,
        'site_domain_id' => $siteDomain->getKey(),
        'language_id' => $siteDomain->language_id,
        'cacheable_type' => $page->getMorphClass(),
        'cacheable_id' => $page->getKey(),
        'cached_at' => now(),
        'last_seen_at' => now(),
    ]);

    htmlCacheMapTestComponent((int) $siteDomain->site_id, $page->getMorphClass())
        ->callTableAction('clear', record: (string) $cachedModelUrl->getKey());

    expect(Storage::disk('page_cache')->exists($cachePath))->toBeFalse()
        ->and(CachedModelUrl::query()->whereKey($cachedModelUrl->getKey())->exists())->toBeFalse();
});

it('hides cache map clear actions from actors without clear permission', function (): void {
    $user = new class extends User
    {
        use HasSitePermissions;

        protected $table = 'users';

        public function getMorphClass(): string
        {
            return User::class;
        }
    };
    $user->forceFill([
        'name' => 'Cache map viewer',
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password'),
    ]);
    $user->save();

    test()->actingAs($user);

    [$siteDomain, $page] = EloquentModel::withoutEvents(function (): array {
        $siteDomain = SiteDomain::factory()->create([
            'scheme' => 'https',
            'domain' => 'example.test',
            'path' => null,
        ]);

        return [
            $siteDomain,
            Page::factory()
                ->recycle($siteDomain->site)
                ->withTranslations()
                ->create(),
        ];
    });
    $role = Role::findOrCreate('editor', 'web');
    DB::table('model_has_roles')->insert([
        'role_id' => $role->getKey(),
        'model_type' => $user->getMorphClass(),
        'model_id' => $user->getKey(),
        'team_id' => $siteDomain->site_id,
    ]);
    $cachedModelUrl = CachedModelUrl::query()->create([
        'url' => 'https://example.test/about',
        'url_hash' => CachedModelUrl::hashUrl('https://example.test/about'),
        'path' => '/about',
        'site_id' => $siteDomain->site_id,
        'site_domain_id' => $siteDomain->getKey(),
        'language_id' => $siteDomain->language_id,
        'cacheable_type' => $page->getMorphClass(),
        'cacheable_id' => $page->getKey(),
        'cached_at' => now(),
        'last_seen_at' => now(),
    ]);

    htmlCacheMapTestComponent((int) $siteDomain->site_id, $page->getMorphClass())
        ->assertCanSeeTableRecords([$cachedModelUrl])
        ->assertTableActionHidden('clear', record: (string) $cachedModelUrl->getKey());
});

it('denies cached model url resource rows when no actor is available', function (): void {
    $siteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'example.test',
        'path' => null,
    ]);
    $page = Page::factory()
        ->recycle($siteDomain->site)
        ->withTranslations()
        ->create();

    CachedModelUrl::query()->create([
        'url' => 'https://example.test/about',
        'url_hash' => CachedModelUrl::hashUrl('https://example.test/about'),
        'path' => '/about',
        'site_id' => $siteDomain->site_id,
        'site_domain_id' => $siteDomain->getKey(),
        'language_id' => $siteDomain->language_id,
        'cacheable_type' => $page->getMorphClass(),
        'cacheable_id' => $page->getKey(),
        'cached_at' => now(),
        'last_seen_at' => now(),
    ]);

    expect(CachedModelUrlResource::getEloquentQuery()->count())->toBe(0);
});

it('requires cache map view permission for the cached model url resource', function (): void {
    resolve(PermissionRegistrar::class)->setPermissionsTeamId(null);
    Permission::findOrCreate(HtmlCachePermission::ViewCachedModelUrls->value, 'web');

    $viewer = User::factory()->create();
    test()->actingAs($viewer);

    expect(CachedModelUrlResource::canAccess())->toBeFalse()
        ->and(CachedModelUrlResource::canViewAny())->toBeFalse();

    $viewer->givePermissionTo(HtmlCachePermission::ViewCachedModelUrls->value);
    resolve(PermissionRegistrar::class)->forgetCachedPermissions();

    expect(CachedModelUrlResource::canAccess())->toBeTrue()
        ->and(CachedModelUrlResource::canViewAny())->toBeTrue();
});

it('reports an amber diagnostic when cached html cannot be inspected', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    test()->actingAs($user);

    $siteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'example.test',
        'path' => null,
    ]);
    $page = Page::factory()
        ->recycle($siteDomain->site)
        ->withTranslations()
        ->create();

    CachedModelUrl::query()->create([
        'url' => 'https://example.test/about',
        'url_hash' => CachedModelUrl::hashUrl('https://example.test/about'),
        'path' => '/about',
        'site_id' => $siteDomain->site_id,
        'site_domain_id' => $siteDomain->getKey(),
        'language_id' => $siteDomain->language_id,
        'cacheable_type' => $page->getMorphClass(),
        'cacheable_id' => $page->getKey(),
        'cached_at' => now(),
        'last_seen_at' => now(),
    ]);

    app()->instance(HtmlCacheStore::class, new class
    {
        public function path(string $file): ?string
        {
            throw new RuntimeException('page_cache disk unavailable');
        }
    });

    $checks = BuildHtmlCachePublicOutputSafetyDiagnosticsAction::run();

    expect($checks)->toHaveCount(1)
        ->and($checks[0]->status)->toBe('amber')
        ->and($checks[0]->detail)->toBe(__('capell-html-cache::admin.cached_html_inspection_failed'))
        ->and($checks[0]->remediation)->toContain('page_cache disk unavailable');
});

it('does not let an older cached model url registration delete newer dependency rows', function (): void {
    $siteDomain = SiteDomain::factory()->create([
        'scheme' => 'https',
        'domain' => 'example.test',
        'path' => null,
    ]);
    $page = Page::factory()
        ->recycle($siteDomain->site)
        ->withTranslations()
        ->create();
    $translation = $page->translations()->where('language_id', $siteDomain->language_id)->first();
    $url = 'https://example.test/about';
    $olderSeenAt = CarbonImmutable::parse('2026-05-09 10:00:00');
    $newerSeenAt = CarbonImmutable::parse('2026-05-09 10:01:00');

    expect($translation)->toBeInstanceOf(Translation::class);

    RecordCachedModelUrlsAction::run($url, [
        $page->getMorphClass() => [$page->getKey()],
        $translation->getMorphClass() => [$translation->getKey()],
    ], $newerSeenAt);

    RecordCachedModelUrlsAction::run($url, [
        $page->getMorphClass() => [$page->getKey()],
    ], $olderSeenAt);

    expect(CachedModelUrl::query()->where('url', $url)->count())->toBe(2)
        ->and(CachedModelUrl::query()
            ->where('url', $url)
            ->where('cacheable_type', $translation->getMorphClass())
            ->exists())->toBeTrue();
});

it('allows multiple cached model url registration jobs for the same url to queue', function (): void {
    expect(new RegisterCachedModelUrlsJob('https://example.test/about', []))
        ->not->toBeInstanceOf(ShouldBeUnique::class);
});
