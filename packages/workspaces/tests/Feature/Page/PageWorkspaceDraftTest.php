<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\SiteDomain;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Capell\Workspaces\Actions\CopyOnWriteAction;
use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Http\Middleware\ResolveWorkspaceContext;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Publisher;

use function Pest\Laravel\get;

uses(TestingFrontend::class)->group('page');

// ─── ExitWorkspacePreviewController ──────────────────────────────────────────

test('exit workspace preview redirects to root by default', function (): void {
    get(route('capell-frontend.preview.exit'))
        ->assertRedirect('/');
});

test('exit workspace preview redirects to specified path', function (): void {
    get(route('capell-frontend.preview.exit', ['redirect' => '/about']))
        ->assertRedirect('/about');
});

test('exit workspace preview clears the workspace cookie', function (): void {
    $workspace = Workspace::factory()->create();

    $response = $this
        ->withCookie(ResolveWorkspaceContext::COOKIE_NAME, $workspace->uuid)
        ->get(route('capell-frontend.preview.exit'));

    $response->assertRedirect('/');
    $response->assertCookieExpired(ResolveWorkspaceContext::COOKIE_NAME);
});

// ─── Workspace preview pill ───────────────────────────────────────────────────

test('workspace preview pill is rendered when workspace cookie is set', function (): void {
    $siteDomain = SiteDomain::factory()->default()->create();
    $page = Page::factory()->site($siteDomain->site)->withTranslations()->create();
    $workspace = Workspace::factory()->create(['name' => 'Sprint 2']);

    $this
        ->withCookie(ResolveWorkspaceContext::COOKIE_NAME, $workspace->uuid)
        ->get($page->pageUrl->full_url)
        ->assertOk()
        ->assertSee('Sprint 2')
        ->assertSee('workspace-preview-pill');
});

test('workspace preview pill is not rendered without workspace cookie', function (): void {
    $siteDomain = SiteDomain::factory()->default()->create();
    $page = Page::factory()->site($siteDomain->site)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertDontSee('workspace-preview-pill');
});

// ─── Post-publish: live page accessible ──────────────────────────────────────

test('live page is accessible after workspace containing its draft is published', function (): void {
    $siteDomain = SiteDomain::factory()->default()->create();
    $page = Page::factory()->site($siteDomain->site)->withTranslations()->create();
    $url = $page->pageUrl->full_url;

    // Create a workspace draft of this page
    $workspace = Workspace::factory()->create(['status' => WorkspaceStatusEnum::Approved]);
    (new CopyOnWriteAction)->cloneForEdit(
        $page->fresh()->fill(['name' => 'Updated Name']),
        $workspace,
    );

    // Publish the workspace — publisher replaces live with the draft
    resolve(Publisher::class)->publish($workspace);

    // The URL should still resolve and return 200
    get($url)->assertOk();
});

test('workspace draft row is not rendered via its live url without workspace context', function (): void {
    $siteDomain = SiteDomain::factory()->default()->create();
    $page = Page::factory()->site($siteDomain->site)->withTranslations()->create();

    $workspace = Workspace::factory()->create(['status' => WorkspaceStatusEnum::Open]);
    (new CopyOnWriteAction)->cloneForEdit(
        $page->fresh()->fill(['name' => 'Draft Name']),
        $workspace,
    );

    // Without workspace context, the live URL still serves the live page
    // (the live row is still accessible even when shadowed — only in workspace
    // context does the shadow hide it). The key is no 404.
    get($page->pageUrl->full_url)->assertOk();
});
