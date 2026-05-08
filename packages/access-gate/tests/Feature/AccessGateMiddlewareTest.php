<?php

declare(strict_types=1);

use Capell\AccessGate\Actions\CreateAccessGateBrowserTokenAction;
use Capell\AccessGate\Actions\CreateAccessGateClaimTokenAction;
use Capell\AccessGate\Actions\CreateAccessGateGrantAction;
use Capell\AccessGate\Contracts\AccessRequestMethod;
use Capell\AccessGate\Contracts\RegistrationField;
use Capell\AccessGate\Data\RegistrationFieldValue;
use Capell\AccessGate\Enums\AccessAreaStatus;
use Capell\AccessGate\Enums\ApprovalStrategy;
use Capell\AccessGate\Enums\BrowserTokenStatus;
use Capell\AccessGate\Enums\GrantSubjectType;
use Capell\AccessGate\Enums\IdentityMode;
use Capell\AccessGate\Http\Middleware\AccessGateMiddleware;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Models\BrowserToken;
use Capell\AccessGate\Models\Grant;
use Capell\AccessGate\Models\Registration;
use Capell\AccessGate\Notifications\AccessRequestReceivedNotification;
use Capell\AccessGate\Support\AccessRequestMethodRegistry;
use Capell\AccessGate\Support\RegistrationFieldRegistry;
use Capell\AccessGate\Tests\Support\FakePageCacheMiddleware;
use Capell\AccessGate\Tests\TestCase;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Auth\User as AuthenticatableUser;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

uses(TestCase::class);

it('blocks protected content before the route renders', function (): void {
    $rendered = false;

    FakePageCacheMiddleware::$ran = false;

    Area::factory()->create([
        'key' => 'preview',
        'identity_mode' => IdentityMode::Hybrid,
    ]);

    Route::middleware('access-gate:preview')->get('/access-gate-test/protected', function () use (&$rendered): string {
        $rendered = true;

        return 'secret';
    });

    $this->get('/access-gate-test/protected')
        ->assertRedirect(route('capell-access-gate.request', [
            'area' => 'preview',
            'redirect' => 'http://localhost/access-gate-test/protected',
        ]));

    expect($rendered)->toBeFalse();
});

it('fails closed when a protected route references an unknown access area', function (): void {
    $rendered = false;

    Route::middleware('access-gate:missing-preview')->get('/access-gate-test/missing-area', function () use (&$rendered): string {
        $rendered = true;

        return 'secret';
    });

    $this->get('/access-gate-test/missing-area')
        ->assertRedirect(route('capell-access-gate.request', [
            'area' => 'missing-preview',
            'redirect' => 'http://localhost/access-gate-test/missing-area',
        ]));

    expect($rendered)->toBeFalse();
});

it('allows protected content when a matching area is paused', function (): void {
    Area::factory()->create([
        'key' => 'preview',
        'status' => AccessAreaStatus::Paused,
        'identity_mode' => IdentityMode::Hybrid,
    ]);

    Route::middleware('access-gate:preview')->get('/access-gate-test/paused', fn (): string => 'secret');

    $this
        ->get('/access-gate-test/paused')
        ->assertOk()
        ->assertSee('secret');
});

it('reports the resolved access area when status checks are denied', function (): void {
    Area::factory()->create([
        'key' => 'preview',
        'status' => AccessAreaStatus::Active,
        'identity_mode' => IdentityMode::Hybrid,
    ]);

    $this
        ->getJson(route('capell-access-gate.status', ['area' => 'preview']))
        ->assertOk()
        ->assertJson([
            'allowed' => false,
        ])
        ->assertJsonMissing([
            'area' => 'preview',
        ]);
});

it('allows guest browser tokens and marks protected responses private', function (): void {
    $area = Area::factory()->create([
        'key' => 'preview',
        'identity_mode' => IdentityMode::Hybrid,
    ]);

    $grant = Grant::factory()->for($area, 'area')->create();
    $issuedToken = app(CreateAccessGateBrowserTokenAction::class)->handle($grant);

    Route::middleware('access-gate:preview')->get('/access-gate-test/guest', fn (): string => 'secret');

    $this
        ->withUnencryptedCookie(config('access-gate.cookies.browser_token.name'), $issuedToken->plainTextToken)
        ->get('/access-gate-test/guest')
        ->assertOk()
        ->assertSee('secret')
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('Pragma', 'no-cache')
        ->assertHeader('Expires', '0');
});

it('rejects revoked browser tokens', function (): void {
    $area = Area::factory()->create([
        'key' => 'preview',
        'identity_mode' => IdentityMode::Hybrid,
    ]);

    $grant = Grant::factory()->for($area, 'area')->create();
    $issuedToken = app(CreateAccessGateBrowserTokenAction::class)->handle($grant);
    $issuedToken->token->forceFill([
        'status' => BrowserTokenStatus::Revoked,
        'revoked_at' => now(),
    ])->save();

    Route::middleware('access-gate:preview')->get('/access-gate-test/revoked', fn (): string => 'secret');

    $this
        ->withUnencryptedCookie(config('access-gate.cookies.browser_token.name'), $issuedToken->plainTextToken)
        ->get('/access-gate-test/revoked')
        ->assertRedirect(route('capell-access-gate.request', [
            'area' => 'preview',
            'redirect' => 'http://localhost/access-gate-test/revoked',
        ]));
});

it('allows authenticated user grants', function (): void {
    $area = Area::factory()->create([
        'key' => 'preview',
        'identity_mode' => IdentityMode::Authenticated,
    ]);

    $user = new AccessGateTestUser;
    $user->forceFill(['id' => 123]);

    app(CreateAccessGateGrantAction::class)->handle(
        area: $area,
        subjectType: GrantSubjectType::User,
        userId: 123,
    );

    Route::middleware('access-gate:preview')->get('/access-gate-test/authenticated', fn (): string => 'secret');

    $this
        ->actingAs($user)
        ->get('/access-gate-test/authenticated')
        ->assertOk()
        ->assertSee('secret');
});

it('allows authenticated users with approved email grants', function (): void {
    $area = Area::factory()->create([
        'key' => 'preview',
        'identity_mode' => IdentityMode::Authenticated,
    ]);

    $user = new AccessGateTestUser;
    $user->forceFill([
        'id' => 123,
        'email' => 'mona@example.test',
    ]);

    app(CreateAccessGateGrantAction::class)->handle(
        area: $area,
        subjectType: GrantSubjectType::Email,
        email: 'mona@example.test',
    );

    Route::middleware('access-gate:preview')->get('/access-gate-test/email-grant', fn (): string => 'secret');

    $this
        ->actingAs($user)
        ->get('/access-gate-test/email-grant')
        ->assertOk()
        ->assertSee('secret');
});

it('allows configured public allowlist paths without a grant', function (): void {
    Area::factory()->create([
        'key' => 'preview',
        'identity_mode' => IdentityMode::Hybrid,
        'public_allowlist' => ['access-gate-test/public/*'],
    ]);

    Route::middleware('access-gate:preview')->get('/access-gate-test/public/info', fn (): string => 'public');

    $this
        ->get('/access-gate-test/public/info')
        ->assertOk()
        ->assertSee('public');
});

it('renders and stores configured public request fields', function (): void {
    Notification::fake();

    $area = Area::factory()->create([
        'key' => 'preview',
        'name' => 'Preview',
    ]);

    app(RegistrationFieldRegistry::class)->register(new PublicRequestProviderField);

    $this->get(route('capell-access-gate.request', ['area' => $area->key]))
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertSee('Provider username');

    $this->post(route('capell-access-gate.request.store', ['area' => $area->key]), [
        'email' => 'mona@example.test',
        'provider_username' => 'octocat',
        'requested_url' => 'https://example.test/preview',
    ])->assertRedirect(route('capell-access-gate.request', ['area' => $area->key]));

    $registration = Registration::query()->where('email_normalized', 'mona@example.test')->firstOrFail();

    expect($registration->field_values['provider_username']['value'])->toBe('octocat');
    Notification::assertSentOnDemand(AccessRequestReceivedNotification::class);
});

it('renders host application access request methods after the email request form', function (): void {
    $area = Area::factory()->create([
        'key' => 'preview',
        'name' => 'Preview',
    ]);

    app(AccessRequestMethodRegistry::class)->register(new PublicRequestProviderMethod);

    $this->get(route('capell-access-gate.request', [
        'area' => $area->key,
        'redirect' => 'https://example.test/preview',
    ]))
        ->assertOk()
        ->assertSee('Continue with Provider')
        ->assertSee('Request Access')
        ->assertSee('or')
        ->assertSee('Email address');
});

it('can disable the package email form while leaving host application methods available', function (): void {
    config()->set('access-gate.registration.methods.email.enabled', false);

    $area = Area::factory()->create([
        'key' => 'preview',
        'name' => 'Preview',
    ]);

    app(AccessRequestMethodRegistry::class)->register(new PublicRequestProviderMethod);

    $this->get(route('capell-access-gate.request', ['area' => $area->key]))
        ->assertOk()
        ->assertSee('Continue with Provider')
        ->assertDontSee('Email address');

    $this->post(route('capell-access-gate.request.store', ['area' => $area->key]), [
        'email' => 'mona@example.test',
    ])->assertSessionHasErrors('email');
});

it('throttles public email requests by area, email, and ip address', function (): void {
    Notification::fake();

    $area = Area::factory()->create([
        'key' => 'preview',
    ]);

    for ($requestAttempt = 1; $requestAttempt <= 6; $requestAttempt++) {
        $this->post(route('capell-access-gate.request.store', ['area' => $area->key]), [
            'email' => 'mona@example.test',
        ])->assertRedirect(route('capell-access-gate.request', ['area' => $area->key]));
    }

    $this->post(route('capell-access-gate.request.store', ['area' => $area->key]), [
        'email' => 'mona@example.test',
    ])->assertTooManyRequests();
});

it('does not trust posted user ids on public access requests', function (): void {
    Notification::fake();

    $area = Area::factory()->create([
        'key' => 'preview',
        'identity_mode' => IdentityMode::Authenticated,
        'approval_strategy' => ApprovalStrategy::AutoApprove,
    ]);

    $this->post(route('capell-access-gate.request.store', ['area' => $area->key]), [
        'email' => 'mona@example.test',
        'user_id' => 123,
    ])->assertRedirect(route('capell-access-gate.request', ['area' => $area->key]));

    expect(Registration::query()->firstOrFail()->user_id)->toBeNull()
        ->and(Grant::query()->where('subject_type', GrantSubjectType::User->value)->exists())->toBeFalse()
        ->and(Grant::query()->where('subject_type', GrantSubjectType::Email->value)->exists())->toBeTrue();
});

it('uses the authenticated email when creating authenticated-mode requests', function (): void {
    Notification::fake();

    $area = Area::factory()->create([
        'key' => 'preview',
        'identity_mode' => IdentityMode::Authenticated,
        'approval_strategy' => ApprovalStrategy::AutoApprove,
    ]);

    $user = new AccessGateTestUser;
    $user->forceFill([
        'id' => 123,
        'email' => 'real@example.test',
    ]);

    $this
        ->actingAs($user)
        ->post(route('capell-access-gate.request.store', ['area' => $area->key]), [
            'email' => 'attacker@example.test',
        ])
        ->assertRedirect(route('capell-access-gate.request', ['area' => $area->key]));

    $registration = Registration::query()->firstOrFail();

    expect($registration->email)->toBe('real@example.test')
        ->and($registration->user_id)->toBe(123)
        ->and(Grant::query()->where('subject_type', GrantSubjectType::User->value)->where('subject_id', '123')->exists())->toBeTrue();
});

it('runs access gate before route-level page cache middleware', function (): void {
    $rendered = false;

    FakePageCacheMiddleware::$ran = false;
    FakePageCacheMiddleware::$sawProtectedRequest = false;

    $router = app('router');
    $router->aliasMiddleware('page-cache', FakePageCacheMiddleware::class);
    $middlewarePriority = collect([AccessGateMiddleware::class, 'access-gate', FakePageCacheMiddleware::class])
        ->merge($router->middlewarePriority)
        ->unique()
        ->values()
        ->all();
    $router->middlewarePriority = $middlewarePriority;
    app(HttpKernel::class)->setMiddlewarePriority($middlewarePriority);

    Area::factory()->create([
        'key' => 'preview',
        'identity_mode' => IdentityMode::Hybrid,
    ]);

    Route::middleware(['web', 'page-cache', 'access-gate:preview'])
        ->get('/access-gate-test/page-cache-priority', function () use (&$rendered): string {
            $rendered = true;

            return 'secret';
        });

    $this
        ->get('/access-gate-test/page-cache-priority')
        ->assertRedirect(route('capell-access-gate.request', [
            'area' => 'preview',
            'redirect' => 'http://localhost/access-gate-test/page-cache-priority',
        ]))
        ->assertDontSee('cached secret');

    expect($rendered)->toBeFalse()
        ->and(FakePageCacheMiddleware::$ran)->toBeFalse();
});

it('marks allowed protected requests so compatible page cache middleware can skip reads and writes', function (): void {
    FakePageCacheMiddleware::$ran = false;
    FakePageCacheMiddleware::$sawProtectedRequest = false;

    $router = app('router');
    $router->aliasMiddleware('page-cache', FakePageCacheMiddleware::class);
    $middlewarePriority = collect([AccessGateMiddleware::class, 'access-gate', FakePageCacheMiddleware::class])
        ->merge($router->middlewarePriority)
        ->unique()
        ->values()
        ->all();
    $router->middlewarePriority = $middlewarePriority;
    app(HttpKernel::class)->setMiddlewarePriority($middlewarePriority);

    $area = Area::factory()->create([
        'key' => 'preview',
        'identity_mode' => IdentityMode::Hybrid,
    ]);
    $grant = Grant::factory()->for($area, 'area')->create();
    $issuedToken = app(CreateAccessGateBrowserTokenAction::class)->handle($grant);

    Route::middleware(['web', 'page-cache', 'access-gate:preview'])
        ->get('/access-gate-test/page-cache-allowed', fn (): string => 'secret');

    $this
        ->withUnencryptedCookie(config('access-gate.cookies.browser_token.name'), $issuedToken->plainTextToken)
        ->get('/access-gate-test/page-cache-allowed')
        ->assertOk()
        ->assertSee('secret')
        ->assertDontSee('cached secret')
        ->assertHeader('Cache-Control', 'no-store, private');

    expect(FakePageCacheMiddleware::$ran)->toBeTrue()
        ->and(FakePageCacheMiddleware::$sawProtectedRequest)->toBeTrue();
});

it('claims access with a one-time token and stores the browser token cookie', function (): void {
    $area = Area::factory()->create([
        'claim_url_hosts' => ['example.test'],
    ]);
    $registration = Registration::factory()
        ->for($area, 'area')
        ->create(['requested_url' => 'https://example.test/preview']);
    $grant = Grant::factory()
        ->for($area, 'area')
        ->for($registration, 'registration')
        ->create();
    $issuedClaimToken = app(CreateAccessGateClaimTokenAction::class)->handle($grant);

    $this
        ->withHeader('User-Agent', 'AccessGateTest/1.0')
        ->get(route('capell-access-gate.claim', ['token' => $issuedClaimToken->plainTextToken]))
        ->assertRedirect('https://example.test/preview')
        ->assertCookie(config('access-gate.cookies.browser_token.name'));

    $browserToken = BrowserToken::query()->firstOrFail();

    expect($browserToken->ip_hash)->toBe(hash('sha256', '127.0.0.1'))
        ->and($browserToken->user_agent)->toBe('AccessGateTest/1.0');
});

it('does not redirect claimed users to untrusted requested urls', function (): void {
    $area = Area::factory()->create([
        'claim_url_hosts' => ['example.test'],
    ]);
    $registration = Registration::factory()
        ->for($area, 'area')
        ->create(['requested_url' => 'https://attacker.test/preview']);
    $grant = Grant::factory()
        ->for($area, 'area')
        ->for($registration, 'registration')
        ->create();
    $issuedClaimToken = app(CreateAccessGateClaimTokenAction::class)->handle($grant);

    $this->get(route('capell-access-gate.claim', ['token' => $issuedClaimToken->plainTextToken]))
        ->assertRedirect(url('/'));
});

it('revokes the local browser token on access gate logout', function (): void {
    $area = Area::factory()->create([
        'key' => 'preview',
    ]);
    $grant = Grant::factory()->for($area, 'area')->create();
    $issuedToken = app(CreateAccessGateBrowserTokenAction::class)->handle($grant);

    $this
        ->withCookie(config('access-gate.cookies.browser_token.name'), $issuedToken->plainTextToken)
        ->post(route('capell-access-gate.logout', ['area' => $area->key]))
        ->assertRedirect(route('capell-access-gate.request', ['area' => $area->key]))
        ->assertCookieExpired(config('access-gate.cookies.browser_token.name'));

    expect(BrowserToken::query()->firstOrFail()->status)->toBe(BrowserTokenStatus::Revoked);
});

final class AccessGateTestUser extends AuthenticatableUser
{
    protected $guarded = [];
}

final class PublicRequestProviderField implements RegistrationField
{
    public function key(): string
    {
        return 'provider_username';
    }

    public function label(): string
    {
        return 'Provider username';
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function validate(array $input): RegistrationFieldValue
    {
        $validated = Validator::make($input, [
            'provider_username' => ['required', 'string'],
        ])->validate();

        return new RegistrationFieldValue(
            key: $this->key(),
            value: strtolower((string) $validated['provider_username']),
        );
    }
}

final class PublicRequestProviderMethod implements AccessRequestMethod
{
    public function key(): string
    {
        return 'provider';
    }

    public function label(): string
    {
        return 'Continue with Provider';
    }

    public function description(): ?string
    {
        return 'Request access using the host application provider.';
    }

    public function isEnabled(Area $area): bool
    {
        return $area->key === 'preview';
    }

    public function isPrimary(Area $area): bool
    {
        return $area->key === 'preview';
    }

    public function url(Area $area, ?string $requestedUrl = null): string
    {
        return url('/host-provider/start?' . http_build_query([
            'area' => $area->key,
            'redirect' => $requestedUrl,
        ]));
    }
}
