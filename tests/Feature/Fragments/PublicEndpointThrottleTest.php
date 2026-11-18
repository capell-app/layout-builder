<?php

declare(strict_types=1);

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function (): void {
    RateLimiter::clear('capell-layout-builder-public:' . hash('sha256', '127.0.0.1'));
    config(['capell-layout-builder.public_endpoints.requests_per_minute' => 2]);
});

it('throttles each public layout endpoint per client once the minute budget is spent', function (string $routeName, array $parameters): void {
    $url = route($routeName, $parameters);

    $this->get($url)->assertStatus(404);
    $this->get($url)->assertStatus(404);
    $this->get($url)->assertStatus(429);
})->with([
    'public fragment' => ['capell-layout-builder.fragments.show', ['reference' => 'not-a-reference']],
    'lazy widget' => ['capell-layout-builder.layout-widgets.show', ['reference' => 'not-a-reference']],
    'localised lazy widget' => ['capell-layout-builder.layout-widgets.localized.show', ['siteDomainPath' => 'en', 'reference' => 'not-a-reference']],
]);

it('rejects a burst request using the count returned by the atomic increment', function (): void {
    $limiter = new class(app('cache')->store()) extends Illuminate\Cache\RateLimiter
    {
        #[Override]
        public function tooManyAttempts(mixed $key, mixed $maxAttempts): bool
        {
            return false;
        }

        #[Override]
        public function hit(mixed $key, mixed $decaySeconds = 60): int
        {
            return 3;
        }
    };
    $limiter->for('capell-layout-builder-public', fn (): Limit => Limit::perMinute(2));
    RateLimiter::swap($limiter);

    $this->get(route('capell-layout-builder.fragments.show', ['reference' => 'not-a-reference']))
        ->assertStatus(429);
});

it('shares the request budget across fragment and both lazy widget routes', function (): void {
    $this->get(route('capell-layout-builder.fragments.show', ['reference' => 'invalid']))->assertNotFound();
    $this->get(route('capell-layout-builder.layout-widgets.show', ['reference' => 'invalid']))->assertNotFound();
    $this->get(route('capell-layout-builder.layout-widgets.localized.show', ['siteDomainPath' => 'en', 'reference' => 'invalid']))
        ->assertStatus(429)->assertHeader('Retry-After');
});

it('declares every public layout route in the route contribution and as throttled', function (): void {
    $manifest = json_decode((string) file_get_contents(dirname(__DIR__, 3) . '/capell.json'), true, flags: JSON_THROW_ON_ERROR);

    $contributions = data_get($manifest, 'contributes');
    $routeContribution = collect(is_array($contributions) ? $contributions : [])->firstWhere('type', 'route');
    $publicRoutes = data_get($manifest, 'security.publicSurface.routeNames');

    expect(data_get($routeContribution, 'routes'))->toEqualCanonicalizing([
        'capell-layout-builder.fragments.show',
        'capell-layout-builder.layout-widgets.show',
        'capell-layout-builder.layout-widgets.localized.show',
    ])
        ->and(data_get($manifest, 'security.publicSurface.throttledRoutes'))->toEqualCanonicalizing(is_array($publicRoutes) ? $publicRoutes : []);
});
