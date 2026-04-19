<?php

declare(strict_types=1);

namespace Capell\Plugins\Support;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * Resolves a stable per-install "site id" used when activating paid plugin
 * licenses with anystack.
 *
 * The id has to be stable across requests (so validation heartbeats target
 * the same activation row anystack created at install time) and ideally
 * stable across deploys (so a routine redeploy doesn't burn an activation
 * slot). The simplest way to hit both is to derive it deterministically from
 * APP_KEY + the application name, neither of which changes in a healthy
 * deploy.
 *
 * The trade-off: rotating APP_KEY fragments licenses. That's a rare and
 * already-disruptive event, so treating it as "re-activate paid plugins"
 * is acceptable for a first iteration. A later revision can migrate to a
 * persisted UUID if this turns out to be a real footgun.
 */
final class SiteIdResolver
{
    private const CACHE_KEY = '__capell_plugins_site_id_cache';

    /** @var array<string, string> */
    private static array $cache = [];

    public static function get(): string
    {
        if (array_key_exists(self::CACHE_KEY, self::$cache)) {
            return self::$cache[self::CACHE_KEY];
        }

        $appKey = config('app.key');
        if (! is_string($appKey) || $appKey === '') {
            throw new RuntimeException(
                'Cannot resolve plugin site id: APP_KEY is not configured. Run `php artisan key:generate`.',
            );
        }

        $appName = config('app.name');
        $appNameString = is_string($appName) ? $appName : 'capell';

        // Prefix keeps the hash namespaced so a leaked site id can't be
        // reused as a raw APP_KEY derivation elsewhere.
        $siteId = hash('sha256', 'capell:plugins:site:' . $appKey . ':' . Str::slug($appNameString));

        self::$cache[self::CACHE_KEY] = $siteId;

        return $siteId;
    }

    /**
     * Reset the in-process cache — intended for tests. Production code never
     * needs this; the cache is for avoiding a hash recomputation per call,
     * not for persistence.
     */
    public static function flushCache(): void
    {
        self::$cache = [];
    }
}
