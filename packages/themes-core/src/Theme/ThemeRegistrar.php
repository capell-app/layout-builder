<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Theme;

class ThemeRegistrar
{
    /** @var array<string, string> */
    private static array $themes = [];

    public static function register(string $key, string $label): void
    {
        self::$themes[$key] = $label;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return self::$themes;
    }

    public static function isRegistered(string $key): bool
    {
        return isset(self::$themes[$key]);
    }

    public static function reset(): void
    {
        self::$themes = [];
    }
}
