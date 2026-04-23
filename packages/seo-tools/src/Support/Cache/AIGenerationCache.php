<?php

declare(strict_types=1);

namespace Capell\SeoTools\Support\Cache;

use Illuminate\Support\Facades\Cache;

class AIGenerationCache
{
    public function __construct(private readonly string $driver, private readonly int $ttl) {}

    public function remember(string $key, callable $callback): mixed
    {
        return Cache::driver($this->driver)->remember($key, $this->ttl, $callback);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::driver($this->driver)->get($key, $default);
    }

    public function put(string $key, mixed $value, ?int $ttl = null): void
    {
        Cache::driver($this->driver)->put($key, $value, $ttl ?? $this->ttl);
    }

    public function keyFor(string $type, int|string $id): string
    {
        return $type . ':' . $id;
    }

    public function ttl(): int
    {
        return $this->ttl;
    }
}
