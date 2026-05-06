<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Support;

use Illuminate\Http\Request;

class CanonicalUrl
{
    public const DEFAULT_STRIP_PARAMS = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'fbclid',
        'gclid',
        'ref',
        'source',
    ];

    /**
     * @param  array<int, string>  $stripParams
     */
    public function __construct(private readonly string $url, private readonly array $stripParams = self::DEFAULT_STRIP_PARAMS) {}

    /**
     * @param  array<int, string>  $stripParams
     */
    public static function fromRequest(Request $request, array $stripParams = self::DEFAULT_STRIP_PARAMS): self
    {
        return new self($request->url() . ($request->getQueryString() !== null ? '?' . $request->getQueryString() : ''), $stripParams);
    }

    public function resolve(): string
    {
        $parsed = parse_url($this->url);

        if ($parsed === false) {
            return $this->url;
        }

        $scheme = ($parsed['scheme'] ?? 'https');
        $host = ($parsed['host'] ?? '');
        $path = ($parsed['path'] ?? '/');

        $queryString = $parsed['query'] ?? null;

        if ($queryString !== null) {
            parse_str($queryString, $queryParams);

            foreach ($this->stripParams as $param) {
                unset($queryParams[$param]);
            }

            $queryString = http_build_query($queryParams);
        }

        // Remove trailing slash unless it's the root path
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        $resolved = $scheme . '://' . $host . $path;

        if ($queryString !== null && $queryString !== '') {
            $resolved .= '?' . $queryString;
        }

        return $resolved;
    }

    public function render(): string
    {
        return '<link rel="canonical" href="' . htmlspecialchars($this->resolve(), ENT_QUOTES | ENT_HTML5) . '" />';
    }
}
