<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\LayoutAreas;

use InvalidArgumentException;

final class LayoutAreaRegistry
{
    public const MAIN = 'main';

    private const GLOBAL_SCOPE = '*';

    /**
     * @var array<string, array<string, string>>
     */
    private array $areas = [];

    public function __construct()
    {
        $this->register(self::MAIN, __('capell-layout-builder::generic.main_content_area'));
    }

    public function register(string $key, string $label, ?string $themeKey = null): void
    {
        $areaKey = $this->normalizeAreaKey($key);
        $areaLabel = trim($label);

        throw_if($areaLabel === '', InvalidArgumentException::class, 'Layout area label cannot be empty.');

        $this->areas[$this->scope($themeKey)][$areaKey] = $areaLabel;
    }

    /**
     * @return array<string, string>
     */
    public function options(?string $themeKey = null): array
    {
        return $this->areasForTheme($themeKey);
    }

    public function label(string $key, ?string $themeKey = null): string
    {
        $areaKey = $this->normalizeAreaKey($key);
        $areas = $this->areasForTheme($themeKey);

        return $areas[$areaKey] ?? str($areaKey)->headline()->toString();
    }

    public function normalizeAreaKey(string $key): string
    {
        $normalized = str($key)->slug()->lower()->toString();

        throw_if($normalized === '', InvalidArgumentException::class, 'Layout area key cannot be empty.');
        throw_if(strlen($normalized) > 128, InvalidArgumentException::class, 'Layout area key cannot be longer than 128 characters.');

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $container
     */
    public function containerArea(array $container): string
    {
        $area = $container['meta']['area'] ?? null;

        if (! is_string($area) || trim($area) === '') {
            return self::MAIN;
        }

        return $this->normalizeAreaKey($area);
    }

    /**
     * @return array<string, string>
     */
    private function areasForTheme(?string $themeKey): array
    {
        $areas = $this->areas[self::GLOBAL_SCOPE] ?? [];

        if ($themeKey !== null && $themeKey !== '' && isset($this->areas[$themeKey])) {
            $areas = array_replace($areas, $this->areas[$themeKey]);
        }

        return $areas;
    }

    private function scope(?string $themeKey): string
    {
        return $themeKey === null || $themeKey === ''
            ? self::GLOBAL_SCOPE
            : $themeKey;
    }
}
