<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Language;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

/**
 * Central point for locale metadata used across the themes.
 *
 * Consumers should prefer these accessors over querying the Laravel facades
 * directly so that locale resolution stays consistent between components,
 * middleware, and the SEO generators.
 */
class LanguageManager
{
    /**
     * @var array<string, array{name: string, native: string, dir: 'ltr'|'rtl', short: string}>
     */
    private array $locales;

    private string $fallback;

    /**
     * @param  array<string, array{name: string, native: string, dir: 'ltr'|'rtl', short: string}>|null  $locales
     */
    public function __construct(?array $locales = null, ?string $fallback = null)
    {
        $this->locales = $locales ?? (array) Config::get('capell.locales', $this->defaultLocales());
        $this->fallback = $fallback ?? (string) Config::get('app.fallback_locale', 'en');
    }

    /**
     * Currently active locale code (e.g. `en`, `fr`, `ar`).
     */
    public function active(): string
    {
        return App::getLocale();
    }

    /**
     * @return list<string>
     */
    public function enabled(): array
    {
        return array_keys($this->locales);
    }

    /**
     * @return array<string, array{name: string, native: string, dir: 'ltr'|'rtl', short: string}>
     */
    public function all(): array
    {
        return $this->locales;
    }

    public function direction(?string $locale = null): string
    {
        $code = $locale ?? $this->active();

        return $this->locales[$code]['dir'] ?? 'ltr';
    }

    public function shortCode(?string $locale = null): string
    {
        $code = $locale ?? $this->active();

        return $this->locales[$code]['short'] ?? substr($code, 0, 2);
    }

    public function nativeName(?string $locale = null): string
    {
        $code = $locale ?? $this->active();

        return $this->locales[$code]['native'] ?? strtoupper($code);
    }

    public function fallback(): string
    {
        return $this->fallback;
    }

    public function isRtl(?string $locale = null): bool
    {
        return $this->direction($locale) === 'rtl';
    }

    /**
     * @return array<string, array{name: string, native: string, dir: 'ltr'|'rtl', short: string}>
     */
    private function defaultLocales(): array
    {
        return [
            'en' => ['name' => 'English', 'native' => 'English', 'dir' => 'ltr', 'short' => 'en'],
        ];
    }
}
