<?php

declare(strict_types=1);

namespace Capell\SeoTools\Support;

use Capell\SeoTools\Support\Cache\RateLimitCache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class AiRateLimiter
{
    public function __construct(
        protected RateLimitCache $cache,
        protected array $config,
    ) {}

    public function checkLimit(string $identifier = 'global', ?string $feature = null): void
    {
        if (! ($this->config['enabled'] ?? false)) {
            return;
        }

        $limits = [
            'global' => (int) ($this->config['requests_per_minute'] ?? 60),
            'user:' . $identifier => (int) ($this->config['requests_per_minute'] ?? 60),
            'feature:' . $feature => (int) ($this->config['requests_per_minute'] ?? 60),
        ];
        foreach ($limits as $key => $limit) {
            throw_unless($this->canExecute($key, $limit), RuntimeException::class, 'AI rate limit exceeded for ' . $key);
        }

        foreach (array_keys($limits) as $key) {
            $this->incrementCounter($key);
        }
    }

    public function getRemainingRequests(string $identifier = 'global'): int
    {
        $cacheKey = 'ai_rate_limit_' . $identifier;
        $current = $this->cache->get($cacheKey, ['count' => 0]);
        $limit = (int) ($this->config['requests_per_minute'] ?? 60);

        return max(0, $limit - (int) ($current['count'] ?? 0));
    }

    public function resetLimit(string $identifier = 'global'): void
    {
        $this->cache->forget('ai_rate_limit_' . $identifier);
        Log::info('Rate limit reset', ['identifier' => $identifier]);
    }

    public function allow(string $identifier = 'global'): bool
    {
        try {
            $this->checkLimit($identifier);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    protected function canExecute(string $key, int $limit): bool
    {
        $cacheKey = 'ai_rate_limit_' . $key;
        $current = $this->cache->get($cacheKey, ['count' => 0]);

        return (int) ($current['count'] ?? 0) < $limit;
    }

    protected function incrementCounter(string $key): void
    {
        $cacheKey = 'ai_rate_limit_' . $key;
        $current = $this->cache->get($cacheKey, ['count' => 0]);
        $current['count'] = (int) ($current['count'] ?? 0) + 1;
        $ttl = (int) ($this->config['window_seconds'] ?? 60);
        $this->cache->put($cacheKey, $current, $ttl);
    }
}
