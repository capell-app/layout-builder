<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Performance;

/**
 * Inlines a block of critical CSS into the page `<head>`.
 *
 * Source can be provided as a raw string or a filesystem path. Minification is
 * a conservative whitespace collapse — it does not try to rewrite selectors.
 */
class CriticalCssInliner
{
    /** @var array<string, string> */
    private array $cache = [];

    public function fromString(string $css, bool $minify = true): string
    {
        $css = $minify ? $this->minify($css) : $css;

        return $this->wrap($css);
    }

    public function fromFile(string $path, bool $minify = true): string
    {
        if (! is_file($path) || ! is_readable($path)) {
            return '';
        }

        $key = $path.'|'.(int) $minify;
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $css = (string) file_get_contents($path);
        $rendered = $this->fromString($css, $minify);
        $this->cache[$key] = $rendered;

        return $rendered;
    }

    private function wrap(string $css): string
    {
        return '<style data-capell-critical>'.$css.'</style>';
    }

    private function minify(string $css): string
    {
        // Strip comments.
        $css = (string) preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        // Collapse whitespace around punctuation and runs of whitespace.
        $css = (string) preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
        $css = (string) preg_replace('/\s+/', ' ', $css);

        return trim($css);
    }
}
