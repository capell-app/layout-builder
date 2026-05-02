<?php

declare(strict_types=1);

namespace Capell\Navigation\Enums;

/**
 * Cache keys specific to navigation functionality.
 */
enum NavigationCacheEnum: string
{
    case NavigationNames = 'navigation-names';

    /**
     * Cache key for navigation name set filtered by site and languages.
     */
    public static function navigationNamesKey(int|string|null $siteId, array $languageIds): string
    {
        $languageIdsHash = hash('sha256', json_encode($languageIds));

        return self::NavigationNames->value . '-' . ($siteId ?? 'null') . '-' . $languageIdsHash;
    }
}
