<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Language;

use Illuminate\Support\Facades\Request;

/**
 * Produces `<link rel="alternate" hreflang="...">` tags for the current route,
 * rewriting the locale segment / query parameter as appropriate.
 */
class HreflangGenerator
{
    public function __construct(
        private readonly LanguageManager $languages,
    ) {}

    /**
     * @param  string|null  $baseUrl  Override base URL (useful for multi-domain setups).
     * @return list<array{hreflang: string, href: string}>
     */
    public function entries(?string $baseUrl = null): array
    {
        $current = $baseUrl ?? Request::url();
        $path = parse_url($current, PHP_URL_PATH) ?? '/';
        $scheme = parse_url($current, PHP_URL_SCHEME) ?? 'https';
        $host = parse_url($current, PHP_URL_HOST) ?? Request::getHost();
        $root = $scheme . '://' . $host;

        $entries = [];
        foreach ($this->languages->enabled() as $locale) {
            $entries[] = [
                'hreflang' => $locale,
                'href' => $this->localisedUrl($root, $path, $locale),
            ];
        }

        $entries[] = [
            'hreflang' => 'x-default',
            'href' => $this->localisedUrl($root, $path, $this->languages->fallback()),
        ];

        return $entries;
    }

    public function render(?string $baseUrl = null): string
    {
        $lines = [];
        foreach ($this->entries($baseUrl) as $entry) {
            $lines[] = sprintf(
                '<link rel="alternate" hreflang="%s" href="%s" />',
                htmlspecialchars($entry['hreflang'], ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($entry['href'], ENT_QUOTES, 'UTF-8'),
            );
        }

        return implode("\n", $lines);
    }

    private function localisedUrl(string $root, string $path, string $locale): string
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/')), static fn ($s): bool => $s !== ''));
        $enabled = $this->languages->enabled();

        if ($segments !== [] && in_array($segments[0], $enabled, true)) {
            $segments[0] = $locale;
        } else {
            array_unshift($segments, $locale);
        }

        $candidate = $root . '/' . implode('/', $segments);

        return filter_var($candidate, FILTER_VALIDATE_URL) !== false ? $candidate : $root . '/' . $locale;
    }
}
