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
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function (): void {
    Config::set('capell-frontend-toolbar.enabled', true);
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

it('returns editor_html for admin user with url', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    fakeAdminAccessChecker();

    $site = Site::factory()->create();
    $language = Language::factory()->create();
    SiteDomain::factory()->for($site)->for($language)->create();
    $page = Page::factory()->site($site)->create();
    PageUrl::factory()->for($site)->for($language)->page($page)->create();

    $factory = Mockery::mock(ViewFactory::class);
    $factory->shouldReceive('share')->andReturnNull();
    $viewMock = Mockery::mock(ViewContract::class);
    $viewMock->shouldReceive('render')->andReturn('<div>toolbar</div>');
    $factory->shouldReceive('make')->andReturn($viewMock);
    View::swap($factory);

    $response = postJson(route('capell-frontend.beacon'), [
        'url' => $page->pageUrl->full_url,
    ]);

    $response->assertOk();
    $response->assertJsonFragment(['editor_html' => '<div>toolbar</div>']);
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
});
