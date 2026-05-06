<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Support\Cache;

use Illuminate\Support\Facades\Cache;

class RateLimitCache
{
    public function __construct(private readonly string $driver) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::driver($this->driver)->get($key, $default);
    }

    public function put(string $key, mixed $value, int $seconds): void
    {
        Cache::driver($this->driver)->put($key, $value, $seconds);
    }

    public function forget(string $key): void
    {
        Cache::driver($this->driver)->forget($key);
    }

    public function keyFor(string $id): string
    {
        return 'ai_rate_limit_' . $id;
    }

    public function ttl(): int
    {
        // Default TTL for rate limiting (e.g., 60 seconds)
        return 60;
    }
}
