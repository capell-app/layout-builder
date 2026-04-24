<?php

declare(strict_types=1);

namespace Capell\Navigation\Support\Registry;

use Closure;

class NavigableRegistry
{
    /** @var array<string, Closure> */
    private static array $resolvers = [];

    public static function register(string $morphClass, Closure $resolver): void
    {
        self::$resolvers[$morphClass] = $resolver;
    }

    public static function resolve(string $morphClass, int $id): ?NavigableResult
    {
        if (! isset(self::$resolvers[$morphClass])) {
            return null;
        }

        $model = (self::$resolvers[$morphClass])($id);

        if ($model === null) {
            return null;
        }

        return NavigableResult::fromModel($model);
    }

    public static function has(string $morphClass): bool
    {
        return isset(self::$resolvers[$morphClass]);
    }
}
