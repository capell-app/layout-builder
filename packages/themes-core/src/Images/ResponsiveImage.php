<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Images;

/**
 * Builds responsive `<img>` / `<picture>` markup with srcset, lazy loading,
 * async decoding, explicit dimensions, and alt text.
 *
 * The class is intentionally self-contained — it does not call out to Glide or
 * Intervention Image. Themes can supply a URL transformer via the constructor
 * to wire in whatever image service they prefer.
 */
class ResponsiveImage
{
    private const DEFAULT_WIDTHS = [400, 800, 1200, 1600];

    /**
     * @param  (callable(string, int): string)|null  $urlTransformer
     * @param  list<int>  $widths
     */
    public function __construct(
        private $urlTransformer = null,
        private readonly array $widths = self::DEFAULT_WIDTHS,
    ) {}

    /**
     * @param  array<string, string>  $extraAttributes
     */
    public function render(
        string $src,
        string $alt,
        int $width,
        int $height,
        string $sizes = '(min-width: 1024px) 50vw, 100vw',
        bool $lazy = true,
        array $extraAttributes = [],
    ): string {
        $srcset = $this->buildSrcset($src);
        $attributes = array_merge([
            'src' => $src,
            'srcset' => $srcset,
            'sizes' => $sizes,
            'alt' => $alt,
            'width' => (string) $width,
            'height' => (string) $height,
            'loading' => $lazy ? 'lazy' : 'eager',
            'decoding' => 'async',
            'style' => sprintf('aspect-ratio: %d / %d;', max(1, $width), max(1, $height)),
        ], $extraAttributes);

        return '<img ' . $this->attrs($attributes) . ' />';
    }

    /**
     * @param  array<string, string>  $webpSources  map of media-query => webp URL
     * @param  array<string, string>  $extraAttributes
     */
    public function renderPicture(
        string $src,
        string $alt,
        int $width,
        int $height,
        array $webpSources = [],
        string $sizes = '(min-width: 1024px) 50vw, 100vw',
        bool $lazy = true,
        array $extraAttributes = [],
    ): string {
        $sources = '';
        foreach ($webpSources as $media => $url) {
            $sources .= sprintf(
                '<source media="%s" type="image/webp" srcset="%s" />',
                htmlspecialchars($media, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($this->buildSrcsetFor($url), ENT_QUOTES, 'UTF-8'),
            );
        }

        return '<picture>' . $sources . $this->render($src, $alt, $width, $height, $sizes, $lazy, $extraAttributes) . '</picture>';
    }

    public function buildSrcset(string $src): string
    {
        return $this->buildSrcsetFor($src);
    }

    private function buildSrcsetFor(string $src): string
    {
        $parts = [];
        foreach ($this->widths as $w) {
            $url = $this->urlTransformer
                ? (string) ($this->urlTransformer)($src, $w)
                : $this->appendQuery($src, $w);

            $parts[] = $url . ' ' . $w . 'w';
        }

        return implode(', ', $parts);
    }

    private function appendQuery(string $src, int $width): string
    {
        $separator = str_contains($src, '?') ? '&' : '?';

        return $src . $separator . 'w=' . $width;
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function attrs(array $attributes): string
    {
        $rendered = [];
        foreach ($attributes as $name => $value) {
            if ($value === '' && $name !== 'alt') {
                continue;
            }

            $rendered[] = sprintf('%s="%s"', $name, htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
        }

        return implode(' ', $rendered);
    }
}
