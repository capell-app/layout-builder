<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Cache;

use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

/**
 * Tag-aware cache wrapper used across the theme packages.
 *
 * Falls back gracefully on drivers (file, database) that do not support tags
 * by forwarding calls to the plain cache API.
 */
class ThemeCache
{
    public const TAG_THEME = 'capell:themes';

    public const TAG_ASSETS = 'capell:theme-assets';

    public const TAG_NAVIGATION = 'capell:navigation';

    /**
     * @param  list<string>  $tags
     */
    public function __construct(
        private readonly array $tags = [self::TAG_THEME],
        private readonly ?Repository $store = null,
    ) {}

    /**
     * @template TValue
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    public function remember(string $key, int $ttl, Closure $callback): mixed
    {
        return $this->repository()->remember($this->key($key), $ttl, $callback);
    }

    public function forget(string $key): bool
    {
        return $this->repository()->forget($this->key($key));
    }

    public function forgetThemeAssets(): void
    {
        $this->flushTag(self::TAG_ASSETS);
    }

    public function flush(): void
    {
        foreach ($this->tags as $tag) {
            $this->flushTag($tag);
        }
    }

    private function flushTag(string $tag): void
    {
        $store = $this->store ?? Cache::store();
        if (method_exists($store, 'tags')) {
            try {
                $store->tags($tag)->flush();

                return;
            } catch (\BadMethodCallException) {
                // Driver does not support tags; ignore and let caller rely on TTL.
            }
        }
    }

    private function repository(): Repository
    {
        $base = $this->store ?? Cache::store();

        if (method_exists($base, 'tags')) {
            try {
                return $base->tags($this->tags);
            } catch (\BadMethodCallException) {
                return $base;
            }
        }

        return $base;
    }

    private function key(string $key): string
    {
        return 'capell:'.$key;
    }
}
