<?php

declare(strict_types=1);

use Capell\PublishingStudio\Actions\GenerateWorkspacePreviewUrlAction;
use Capell\PublishingStudio\Http\Middleware\ResolveWorkspaceContext;
use Capell\PublishingStudio\Models\PreviewLink;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\WorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::get('/', fn (): string => 'ok')->name('capell-frontend.index');
    Route::get('{url}', fn (): string => 'ok')
        ->where('url', '.*')
        ->name('capell-frontend.page');
});

it('builds a temporary signed URL containing the workspace uuid for the home page', function (): void {
    $workspace = Workspace::factory()->create();

    $url = (new GenerateWorkspacePreviewUrlAction)->handle($workspace);

    expect($url)
        ->toContain(ResolveWorkspaceContext::QUERY_PARAM . '=' . $workspace->uuid)
        ->toContain('signature=')
        ->toContain('expires=');
});

it('uses the current frontend home route when it is registered', function (): void {
    Route::get('/home-route-preview', fn (): string => 'ok')->name('capell-frontend.home');

    $workspace = Workspace::factory()->create();

    $url = (new GenerateWorkspacePreviewUrlAction)->handle($workspace);

    expect(parse_url($url, PHP_URL_PATH))->toBe('/home-route-preview');
});

it('uses the configured preview home route when it is registered', function (): void {
    config()->set('capell.publishing-studio.preview.home_route', 'capell-frontend.custom-home');
    Route::get('/custom-home-route-preview', fn (): string => 'ok')->name('capell-frontend.custom-home');

    $workspace = Workspace::factory()->create();

    $url = (new GenerateWorkspacePreviewUrlAction)->handle($workspace);

    expect(parse_url($url, PHP_URL_PATH))->toBe('/custom-home-route-preview');
});

it('returns a signed URL using the page route for non-root paths', function (): void {
    $workspace = Workspace::factory()->create();

    $url = (new GenerateWorkspacePreviewUrlAction)->handle($workspace, '/about/team');

    expect($url)
        ->toContain('about/team')
        ->toContain(ResolveWorkspaceContext::QUERY_PARAM . '=' . $workspace->uuid)
        ->toContain('signature=');
});

it('resolves the signed URL through the middleware and sets the workspace context', function (): void {
    $workspace = Workspace::factory()->create();

    $url = (new GenerateWorkspacePreviewUrlAction)->handle($workspace);

    $request = Request::create($url);

    (new ResolveWorkspaceContext)->handle($request, fn (): Response => new Response('ok'));

    expect(WorkspaceContext::currentId())->toBe($workspace->id);
});

it('persists a PreviewLink row and embeds its token in the signed URL', function (): void {
    $workspace = Workspace::factory()->create();

    $url = (new GenerateWorkspacePreviewUrlAction)->handle($workspace);

    $link = PreviewLink::query()->where('workspace_id', $workspace->id)->firstOrFail();

    expect($url)
        ->toContain(ResolveWorkspaceContext::TOKEN_PARAM . '=' . $link->token)
        ->and($link->isUsable())->toBeTrue()
        ->and($link->expires_at)->not->toBeNull();
});
