<?php

declare(strict_types=1);

use Capell\PublishingStudio\Http\Middleware\ResolveWorkspaceContext;
use Capell\PublishingStudio\Models\PreviewLink;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\WorkspaceContext;
use Capell\Tests\Fixtures\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Cookie;

beforeEach(function (): void {
    WorkspaceContext::clear();

    Route::get('/_workspace-preview-test', fn (): Response => new Response('ok'))
        ->name('workspace-preview-test');
});

afterEach(function (): void {
    WorkspaceContext::clear();
});

function invokeWorkspaceMiddleware(Request $request): Response
{
    $middleware = new ResolveWorkspaceContext;

    return $middleware->handle($request, fn (): Response => new Response('ok'));
}

it('resolves a workspace from a valid signed URL with preview token without dropping a persistent cookie', function (): void {
    $workspace = Workspace::factory()->create();
    $link = PreviewLink::query()->create([
        'workspace_id' => $workspace->id,
        'token' => PreviewLink::generateToken(),
        'issued_at' => CarbonImmutable::now(),
        'expires_at' => CarbonImmutable::now()->addHour(),
    ]);

    $signedUrl = URL::temporarySignedRoute(
        'workspace-preview-test',
        now()->addHour(),
        [
            ResolveWorkspaceContext::QUERY_PARAM => $workspace->uuid,
            ResolveWorkspaceContext::TOKEN_PARAM => $link->token,
        ],
    );

    $response = invokeWorkspaceMiddleware(Request::create($signedUrl));

    $responseCookies = array_filter(
        $response->headers->getCookies(),
        fn (Cookie $cookie): bool => $cookie->getName() === ResolveWorkspaceContext::COOKIE_NAME,
    );

    expect(WorkspaceContext::current())->not->toBeNull()
        ->and(WorkspaceContext::current()?->id)->toBe($workspace->id)
        ->and($responseCookies)->toBeEmpty();
});

it('rejects a signed URL that carries a workspace uuid without a preview token', function (): void {
    $workspace = Workspace::factory()->create();

    $signedUrl = URL::temporarySignedRoute(
        'workspace-preview-test',
        now()->addHour(),
        [ResolveWorkspaceContext::QUERY_PARAM => $workspace->uuid],
    );

    $response = invokeWorkspaceMiddleware(Request::create($signedUrl));

    $responseCookies = array_filter(
        $response->headers->getCookies(),
        fn (Cookie $cookie): bool => $cookie->getName() === ResolveWorkspaceContext::COOKIE_NAME,
    );

    expect(WorkspaceContext::current())->toBeNull()
        ->and($responseCookies)->toBeEmpty();
});

it('rejects a preview token whose workspace uuid does not match the signed URL', function (): void {
    $workspaceA = Workspace::factory()->create();
    $workspaceB = Workspace::factory()->create();
    $link = PreviewLink::query()->create([
        'workspace_id' => $workspaceA->id,
        'token' => PreviewLink::generateToken(),
        'issued_at' => CarbonImmutable::now(),
        'expires_at' => CarbonImmutable::now()->addHour(),
    ]);

    $signedUrl = URL::temporarySignedRoute(
        'workspace-preview-test',
        now()->addHour(),
        [
            ResolveWorkspaceContext::QUERY_PARAM => $workspaceB->uuid,
            ResolveWorkspaceContext::TOKEN_PARAM => $link->token,
        ],
    );

    invokeWorkspaceMiddleware(Request::create($signedUrl));

    expect(WorkspaceContext::current())->toBeNull();
});

it('resolves a workspace from a cookie when no signed URL is present', function (): void {
    $workspace = Workspace::factory()->create();

    $request = Request::create('/_workspace-preview-test');
    $request->cookies->set(ResolveWorkspaceContext::COOKIE_NAME, $workspace->uuid);

    invokeWorkspaceMiddleware($request);

    expect(WorkspaceContext::current())->not->toBeNull()
        ->and(WorkspaceContext::current()?->id)->toBe($workspace->id);
});

it('does not drop a cookie when resolution came from an existing cookie', function (): void {
    $workspace = Workspace::factory()->create();

    $request = Request::create('/_workspace-preview-test');
    $request->cookies->set(ResolveWorkspaceContext::COOKIE_NAME, $workspace->uuid);

    $response = invokeWorkspaceMiddleware($request);

    $responseCookies = array_filter(
        $response->headers->getCookies(),
        fn (Cookie $cookie): bool => $cookie->getName() === ResolveWorkspaceContext::COOKIE_NAME,
    );

    expect($responseCookies)->toBeEmpty();
});

it('resolves a workspace from the session when no signed URL or cookie is present', function (): void {
    $workspace = Workspace::factory()->create();

    $sessionStore = new Store('test', new ArraySessionHandler(30));
    $sessionStore->put(ResolveWorkspaceContext::SESSION_KEY, $workspace->id);

    $request = Request::create('/_workspace-preview-test');
    $request->setLaravelSession($sessionStore);

    invokeWorkspaceMiddleware($request);

    expect(WorkspaceContext::current())->not->toBeNull()
        ->and(WorkspaceContext::current()?->id)->toBe($workspace->id);
});

it('falls back to null context when no hint is supplied', function (): void {
    $response = invokeWorkspaceMiddleware(Request::create('/_workspace-preview-test'));

    $responseCookies = array_filter(
        $response->headers->getCookies(),
        fn (Cookie $cookie): bool => $cookie->getName() === ResolveWorkspaceContext::COOKIE_NAME,
    );

    expect(WorkspaceContext::current())->toBeNull()
        ->and($responseCookies)->toBeEmpty();
});

it('ignores a signed URL with a bogus signature', function (): void {
    $workspace = Workspace::factory()->create();

    $request = Request::create(
        '/_workspace-preview-test?' . http_build_query([
            ResolveWorkspaceContext::QUERY_PARAM => $workspace->uuid,
            'signature' => 'bogus',
            'expires' => now()->addHour()->timestamp,
        ]),
    );

    invokeWorkspaceMiddleware($request);

    expect(WorkspaceContext::current())->toBeNull();
});

it('resolves workspace via valid preview link token and increments access metadata', function (): void {
    $workspace = Workspace::factory()->create();
    $link = PreviewLink::query()->create([
        'workspace_id' => $workspace->id,
        'token' => PreviewLink::generateToken(),
        'issued_at' => CarbonImmutable::now(),
        'expires_at' => CarbonImmutable::now()->addHour(),
    ]);

    $signedUrl = URL::temporarySignedRoute(
        'workspace-preview-test',
        now()->addHour(),
        [
            ResolveWorkspaceContext::QUERY_PARAM => $workspace->uuid,
            ResolveWorkspaceContext::TOKEN_PARAM => $link->token,
        ],
    );

    invokeWorkspaceMiddleware(Request::create($signedUrl));

    $link->refresh();

    expect(WorkspaceContext::current()?->id)->toBe($workspace->id)
        ->and($link->access_count)->toBe(1)
        ->and($link->last_accessed_at)->not->toBeNull();
});

it('rejects a revoked preview link token', function (): void {
    $workspace = Workspace::factory()->create();
    $link = PreviewLink::query()->create([
        'workspace_id' => $workspace->id,
        'token' => PreviewLink::generateToken(),
        'issued_at' => CarbonImmutable::now()->subHour(),
        'expires_at' => CarbonImmutable::now()->addHour(),
        'revoked_at' => CarbonImmutable::now()->subMinute(),
    ]);

    $signedUrl = URL::temporarySignedRoute(
        'workspace-preview-test',
        now()->addHour(),
        [
            ResolveWorkspaceContext::QUERY_PARAM => $workspace->uuid,
            ResolveWorkspaceContext::TOKEN_PARAM => $link->token,
        ],
    );

    invokeWorkspaceMiddleware(Request::create($signedUrl));

    expect(WorkspaceContext::current())->toBeNull();
});

it('rejects an expired preview link token even if the URL signature is still valid', function (): void {
    $workspace = Workspace::factory()->create();
    $link = PreviewLink::query()->create([
        'workspace_id' => $workspace->id,
        'token' => PreviewLink::generateToken(),
        'issued_at' => CarbonImmutable::now()->subHours(2),
        'expires_at' => CarbonImmutable::now()->subMinute(),
    ]);

    $signedUrl = URL::temporarySignedRoute(
        'workspace-preview-test',
        now()->addHour(),
        [
            ResolveWorkspaceContext::QUERY_PARAM => $workspace->uuid,
            ResolveWorkspaceContext::TOKEN_PARAM => $link->token,
        ],
    );

    invokeWorkspaceMiddleware(Request::create($signedUrl));

    expect(WorkspaceContext::current())->toBeNull();
});

it('ignores a cookie-supplied workspace for an authenticated user who lacks view permission', function (): void {
    $workspace = Workspace::factory()->create();

    $user = User::factory()->create();

    $request = Request::create('/_workspace-preview-test');
    $request->cookies->set(ResolveWorkspaceContext::COOKIE_NAME, $workspace->uuid);
    $request->setUserResolver(fn () => $user);

    invokeWorkspaceMiddleware($request);

    expect(WorkspaceContext::current())->toBeNull();
});

it('ignores a session-supplied workspace for an authenticated user who lacks view permission', function (): void {
    $workspace = Workspace::factory()->create();

    $user = User::factory()->create();

    $sessionStore = new Store('test', new ArraySessionHandler(30));
    $sessionStore->put(ResolveWorkspaceContext::SESSION_KEY, $workspace->id);

    $request = Request::create('/_workspace-preview-test');
    $request->setLaravelSession($sessionStore);
    $request->setUserResolver(fn () => $user);

    invokeWorkspaceMiddleware($request);

    expect(WorkspaceContext::current())->toBeNull();
});
