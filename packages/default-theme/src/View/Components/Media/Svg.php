<?php

declare(strict_types=1);

namespace Capell\DefaultTheme\View\Components\Media;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Svg extends Component
{
    /**
     * SVG elements that can execute script or load remote content. Stripped
     * from every parsed SVG before render so a maliciously-crafted file
     * (e.g. uploaded as a site logo) cannot execute JavaScript in a guest's
     * browser.
     */
    /** @var list<string> */
    private const DANGEROUS_TAGS = [
        'script',
        'foreignObject',
        'iframe',
        'object',
        'embed',
        'handler',
        'style',
        'animate',
        'animateMotion',
        'animateTransform',
        'set',
    ];

    public string $contents;

    public string $height;

    public string $width;

    public string $viewBox;

    /**
     * @param  string  $path  Path to the SVG file
     * @param  string|null  $width  Optional width override
     * @param  string|null  $height  Optional height override
     * @param  string|null  $viewBox  Optional viewBox override
     */
    public function __construct(
        string $path,
        ?string $width = null,
        ?string $height = null,
        ?string $viewBox = null,
    ) {
        $dom = new DOMDocument('1.2', 'utf-8');
        $dom->load($path, LIBXML_NONET);

        $svg = $dom->documentElement;

        if ($svg instanceof DOMElement) {
            $this->sanitize($dom, $svg);
        }

        $svgViewBox = $svg?->getAttribute('viewBox') ?? '';
        $svgWidth = $svg?->getAttribute('width') ?? '';
        $svgHeight = $svg?->getAttribute('height') ?? '';

        if ($viewBox !== null) {
            $this->viewBox = $viewBox;
        } elseif ($svgViewBox !== '') {
            $this->viewBox = $svgViewBox;
        } elseif ($svgWidth !== '' && $svgHeight !== '') {
            $this->viewBox = '0 0 ' . $svgWidth . ' ' . $svgHeight;
        } else {
            $this->viewBox = '';
        }

        $this->width = $width ?? $svgWidth;
        $this->height = $height ?? $svgHeight;

        $this->contents = '';
        if ($svg instanceof DOMElement) {
            foreach ($svg->childNodes as $node) {
                $this->contents .= $dom->saveXML($node);
            }
        }
    }

    public function render(): View
    {
        return view('capell::components.media.svg');
    }

    /**
     * Remove dangerous elements and attributes from the SVG. Walks the
     * DOM bottom-up so removing a node doesn't disturb the iteration.
     */
    private function sanitize(DOMDocument $dom, DOMElement $root): void
    {
        $xpath = new DOMXPath($dom);

        $dangerousSelector = implode('|', array_map(
            static fn (string $tag): string => '//*[local-name()="' . $tag . '"]',
            self::DANGEROUS_TAGS,
        ));

        $matches = $xpath->query($dangerousSelector);
        if ($matches !== false) {
            foreach (iterator_to_array($matches) as $node) {
                if ($node instanceof DOMNode) {
                    $node->parentNode?->removeChild($node);
                }
            }
        }

        $this->stripDangerousAttributes($root);
    }

    private function stripDangerousAttributes(DOMElement $element): void
    {
        /** @var list<DOMAttr> $attrs */
        $attrs = [];
        foreach ($element->attributes ?? [] as $attr) {
            if ($attr instanceof DOMAttr) {
                $attrs[] = $attr;
            }
        }

        foreach ($attrs as $attr) {
            $name = strtolower($attr->name);
            $value = $attr->value;

            if (str_starts_with($name, 'on')) {
                $element->removeAttributeNode($attr);

                continue;
            }

            if (($name === 'href' || $name === 'xlink:href') && $this->isUnsafeReferenceUrl($value)) {
                $element->removeAttributeNode($attr);

                continue;
            }

            if ($name === 'style' && $this->containsUnsafeCss($value)) {
                $element->removeAttributeNode($attr);
            }
        }

        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $this->stripDangerousAttributes($child);
            }
        }
    }

    private function isUnsafeReferenceUrl(string $url): bool
    {
        $normalized = $this->normalizeUrl($url);

        if ($normalized === '') {
            return true;
        }

        if ($this->hasDangerousScheme($normalized)) {
            return true;
        }

        if (str_starts_with($normalized, 'http://')
            || str_starts_with($normalized, 'https://')
            || str_starts_with($normalized, '//')) {
            return true;
        }

        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $normalized) === 1) {
            return true;
        }

        return preg_match('/^[A-Za-z0-9._~\/#?=&:%-]+$/', $url) !== 1;
    }

    private function containsUnsafeCss(string $css): bool
    {
        $normalized = strtolower($css);

        if (str_contains($normalized, '@import')
            || str_contains($normalized, 'expression(')
            || str_contains($normalized, '-moz-binding')) {
            return true;
        }

        preg_match_all('/url\(\s*([^)]+?)\s*\)/i', $css, $matches);

        foreach ($matches[1] as $rawUrl) {
            $url = trim($rawUrl, " \t\n\r\0\x0B'\"");

            if ($this->isUnsafeReferenceUrl($url)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeUrl(string $url): string
    {
        $decoded = html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return strtolower((string) preg_replace('/[\x00-\x20]+/', '', $decoded));
    }

    private function hasDangerousScheme(string $normalizedUrl): bool
    {
        return str_starts_with($normalizedUrl, 'javascript:')
            || str_starts_with($normalizedUrl, 'data:')
            || str_starts_with($normalizedUrl, 'vbscript:');
    }
}
