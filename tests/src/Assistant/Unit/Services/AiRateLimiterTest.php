<?php

declare(strict_types=1);

use Capell\SeoTools\Support\AiRateLimiter;
use Capell\SeoTools\Support\Cache\RateLimitCache;
use Illuminate\Support\Facades\Date;

it('allows up to N requests per window and blocks subsequent ones', function (): void {
    $cache = new RateLimitCache('array');
    $config = ['enabled' => true, 'requests_per_minute' => 3];
    $limit = new AiRateLimiter($cache, $config);

    expect($limit->allow('user-1'))->toBeTrue();
    expect($limit->allow('user-1'))->toBeTrue();
    expect($limit->allow('user-1'))->toBeTrue();

    expect($limit->allow('user-1'))->toBeFalse();
});

it('resets window after time passes', function (): void {
    $cache = new RateLimitCache('array');
    $config = ['enabled' => true, 'requests_per_minute' => 1, 'window_seconds' => 1];
    $limit = new AiRateLimiter($cache, $config);

    Date::setTestNow(now());
    expect($limit->allow('user-1'))->toBeTrue();

    // Simulate wait by advancing time by 2 seconds
    Date::setTestNow(now()->addSeconds(2));

    expect($limit->allow('user-1'))->toBeTrue();

    Date::setTestNow(); // Clear test time
});
