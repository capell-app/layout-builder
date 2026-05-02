<?php

declare(strict_types=1);

namespace Capell\Backup\Policy;

use Capell\Backup\Enums\RelationOwnership;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Type;
use RuntimeException;

/**
 * Declares, for every Capell model that may travel in a content package,
 * whether the relation is OWNED (inlined with its parent) or SHARED
 * (exported once under /relations and referenced by stable key from pages).
 *
 * Packages can extend the map via OwnershipMap::register().
 */
final class OwnershipMap
{
    /** @var array<class-string, RelationOwnership> */
    private const DEFAULTS = [
        // Owned — travel inline under their parent page/site.
        PageUrl::class => RelationOwnership::Owned,

        // Shared — exported once, referenced by ref.
        Page::class => RelationOwnership::Shared,
        Site::class => RelationOwnership::Shared,
        SiteDomain::class => RelationOwnership::Shared,
        Layout::class => RelationOwnership::Shared,
        Type::class => RelationOwnership::Shared,
        Media::class => RelationOwnership::Shared,
    ];

    /** @var array<class-string, RelationOwnership> */
    private static array $overrides = [];

    /**
     * @param  class-string  $modelClass
     */
    public static function register(string $modelClass, RelationOwnership $ownership): void
    {
        self::$overrides[$modelClass] = $ownership;
    }

    public static function reset(): void
    {
        self::$overrides = [];
    }

    /**
     * @param  class-string  $modelClass
     */
    public static function for(string $modelClass): RelationOwnership
    {
        if (isset(self::$overrides[$modelClass])) {
            return self::$overrides[$modelClass];
        }

        if (isset(self::DEFAULTS[$modelClass])) {
            return self::DEFAULTS[$modelClass];
        }

        throw new RuntimeException(sprintf(
            'No OwnershipMap entry registered for model [%s]. Call OwnershipMap::register() first.',
            $modelClass,
        ));
    }

    /**
     * @param  class-string  $modelClass
     */
    public static function isOwned(string $modelClass): bool
    {
        return self::for($modelClass) === RelationOwnership::Owned;
    }

    /**
     * @param  class-string  $modelClass
     */
    public static function isShared(string $modelClass): bool
    {
        return self::for($modelClass) === RelationOwnership::Shared;
    }

    /**
     * @return array<class-string, RelationOwnership>
     */
    public static function all(): array
    {
        return array_merge(self::DEFAULTS, self::$overrides);
    }
}
