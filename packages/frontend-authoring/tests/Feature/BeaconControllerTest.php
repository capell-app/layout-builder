<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Frontend\Contracts\AdminAccessCheckerInterface;
use Capell\Tests\Fixtures\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Config;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function (): void {
    Config::set('capell-frontend-authoring.enabled', true);
});

function fakeAdminAccessChecker(bool $isAdmin = true): void
{
    app()->instance(AdminAccessCheckerInterface::class, new class($isAdmin) implements AdminAccessCheckerInterface
    {
        public function __construct(private readonly bool $isAdmin) {}

        public function isAdmin(Authenticatable $user): bool
        {
            return $this->isAdmin;
        }
    });
}

it('returns 404 if no site domain', function (): void {
    $response = postJson(route('capell-frontend.beacon'), [
        'url' => 'https://example.com/foo',
    ]);

    $response->assertStatus(404);
});

it('returns csrf token and user info for authenticated user', function (): void {
    $user = User::factory()->create(['name' => 'Test User']);
    actingAs($user);

    $site = Site::factory()->create();
    $language = Language::factory()->create();
    $siteDomain = SiteDomain::factory()->for($site)->for($language)->create();

    $response = postJson(route('capell-frontend.beacon'), [
        'url' => $siteDomain->full_url,
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'csrf_token',
        'user' => ['id', 'name'],
    ]);
    $response->assertJson(['user' => ['id' => $user->getKey(), 'name' => 'Test User']]);
});

it('returns beacon scripts for admin user with url', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    fakeAdminAccessChecker();

    $site = Site::factory()->create();
    $language = Language::factory()->create();
    SiteDomain::factory()->for($site)->for($language)->create();
    $page = Page::factory()->site($site)->create();
    PageUrl::factory()->for($site)->for($language)->page($page)->create();

    $response = postJson(route('capell-frontend.beacon'), [
        'url' => $page->pageUrl->full_url,
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'scripts',
    ]);

    expect($response->json('scripts.0'))
        ->toContain('CapellFrontendAuthoring')
        ->toContain('edit_url')
        ->toContain('.capell-authoring-region:hover > .capell-authoring-button');
});

it('does not return authoring scripts or metadata for non-admin authenticated user', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    fakeAdminAccessChecker(false);

    $site = Site::factory()->create();
    $language = Language::factory()->create();
    SiteDomain::factory()->for($site)->for($language)->create();
    $page = Page::factory()->site($site)->create();
    PageUrl::factory()->for($site)->for($language)->page($page)->create();

    $response = postJson(route('capell-frontend.beacon'), [
        'url' => $page->pageUrl->full_url,
    ]);

    $response->assertOk();
    $response->assertJsonMissingPath('scripts');
    $response->assertJsonMissingPath('editable_regions');
    $response->assertJsonMissingPath('editor_html');

    expect($response->getContent())
        ->not->toContain('CapellFrontendAuthoring')
        ->not->toContain('capell-authoring')
        ->not->toContain('edit_url')
        ->not->toContain('recordKey')
        ->not->toContain('meta.description');
});

it('returns only csrf token for guest', function (): void {
    $site = Site::factory()->create();
    $language = Language::factory()->create();
    $siteDomain = SiteDomain::factory()->for($site)->for($language)->create();

    $response = postJson(route('capell-frontend.beacon'), [
        'url' => $siteDomain->full_url,
    ]);

    $response->assertOk();
    $response->assertJsonStructure(['csrf_token']);
    $response->assertJsonMissing(['user']);

    expect($response->getContent())
        ->not->toContain('CapellFrontendAuthoring')
        ->not->toContain('capell-authoring')
        ->not->toContain('edit_url')
        ->not->toContain('recordKey')
        ->not->toContain('meta.description');
});

it('rejects oversized beacon urls', function (): void {
    $response = postJson(route('capell-frontend.beacon'), [
        'url' => 'https://example.com/' . str_repeat('a', 2049),
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['url']);
});

it('throttles beacon requests', function (): void {
    foreach (range(1, 60) as $requestNumber) {
        $response = postJson(route('capell-frontend.beacon'), [
            'url' => 'https://example.com/foo',
        ]);

        $response->assertStatus(404);
    }

    $response = postJson(route('capell-frontend.beacon'), [
        'url' => 'https://example.com/foo',
    ]);

    $response->assertTooManyRequests();
});

it('renders page data without throwing when configured beacon route is missing', function (): void {
    Config::set('capell-page.frontend.route_name', 'capell-frontend.missing-beacon');

    $rendered = view('capell::components.page-data')->render();

    expect($rendered)->toContain('"url":null');
});

it('page data does not render authoring metadata into cached html', function (): void {
    $rendered = view('capell::components.page-data')->render();

    expect($rendered)
        ->not->toContain('editable_regions')
        ->not->toContain('edit_url')
        ->not->toContain('capell-authoring')
        ->not->toContain('CapellFrontendAuthoring')
        ->not->toContain('recordKey')
        ->not->toContain('meta.description');
});
