<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

uses(CreatesAdminUser::class);

function assertAllPackagesFrontendFixtureIsInstalled(): void
{
    expect(CapellCore::getInstalledPackages()->keys()->all())
        ->toContain(
            'capell-app/admin',
            'capell-app/blog',
            'capell-app/campaign-studio',
            'capell-app/content-sections',
            'capell-app/core',
            'capell-app/frontend',
            'capell-app/frontend-authoring',
            'capell-app/insights',
            'capell-app/layout-builder',
            'capell-app/navigation',
            'capell-app/publishing-studio',
            'capell-app/seo-suite',
        );
}

function createAllPackagesFrontendPage(): PageUrl
{
    assertAllPackagesFrontendFixtureIsInstalled();

    $theme = Theme::factory()
        ->default()
        ->meta([
            'footer' => false,
            'header' => false,
        ])
        ->create();

    $layout = Layout::factory()
        ->default()
        ->create();

    $site = Site::factory()
        ->theme($theme)
        ->withTranslations(
            siteDomainData: [
                'default' => true,
                'domain' => 'all-packages.test',
                'path' => null,
                'scheme' => 'https',
                'status' => true,
            ],
        )
        ->create();

    $page = Page::factory()
        ->site($site)
        ->layout($layout)
        ->home()
        ->withTranslations(
            languages: $site->language,
            data: [
                'content' => '<p>Frontend content rendered with every package provider booted.</p>',
                'meta' => [
                    'description' => 'All packages frontend smoke test description.',
                ],
                'title' => 'All Packages Frontend',
            ],
            slug: '/',
        )
        ->create();

    $page->loadMissing('pageUrl.siteDomain');

    expect($page->pageUrl)->toBeInstanceOf(PageUrl::class);

    return $page->pageUrl;
}

function assertNoFrontendAuthoringSurface(TestResponse $response): void
{
    expect($response->getContent())
        ->not->toContain('CapellFrontendAuthoring')
        ->not->toContain('capell-authoring')
        ->not->toContain('authoring/regions')
        ->not->toContain('edit_url')
        ->not->toContain('recordKey')
        ->not->toContain('capell-frontend-authoring');
}

it('renders the frontend website for guests with all package providers booted', function (): void {
    $pageUrl = createAllPackagesFrontendPage();

    $response = get($pageUrl->full_url);

    $response->assertOk();
    $response->assertSeeText('All Packages Frontend');

    expect($response->getContent())->toContain('window.beaconData');

    assertNoFrontendAuthoringSurface($response);
});

it('renders the frontend website for admins without leaking authoring controls into html', function (): void {
    $pageUrl = createAllPackagesFrontendPage();

    test()->actingAsAdmin();

    $response = get($pageUrl->full_url);

    $response->assertOk();
    $response->assertSeeText('All Packages Frontend');

    expect($response->getContent())->toContain('window.beaconData');

    assertNoFrontendAuthoringSurface($response);
});

it('returns admin authoring bootstrap only through the beacon after all packages are booted', function (): void {
    $pageUrl = createAllPackagesFrontendPage();

    test()->actingAsAdmin();

    $response = postJson(route('capell-frontend.beacon'), [
        'url' => $pageUrl->full_url,
    ]);

    $response->assertOk();
    $response->assertJsonPath('user.admin', true);
    $response->assertJsonStructure([
        'csrf_token',
        'scripts',
        'user' => ['admin', 'id', 'name'],
    ]);

    expect($response->json('scripts.0'))
        ->toContain('CapellFrontendAuthoring')
        ->toContain('authoring\\/regions')
        ->toContain('edit_url')
        ->toContain('Page title')
        ->toContain('Page content');
});
