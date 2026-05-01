<?php

declare(strict_types=1);

namespace Capell\Themes\Core\SEO;

use DOMDocument;

class SitemapGenerator
{
    private const VALID_CHANGEFREQ = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];

    private const SITEMAP_NAMESPACE = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    /** @var array<int, array{url: string, lastmod: string|null, changefreq: string, priority: float}> */
    private array $urls = [];

    public function add(string $url, ?string $lastmod = null, string $changefreq = 'monthly', float $priority = 0.5): static
    {
        if (! in_array($changefreq, self::VALID_CHANGEFREQ, true)) {
            $changefreq = 'monthly';
        }

        $priority = max(0.0, min(1.0, $priority));

        $this->urls[] = [
            'url' => $url,
            'lastmod' => $lastmod,
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];

        return $this;
    }

    public function count(): int
    {
        return count($this->urls);
    }

    public function toXml(): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;

        $urlset = $document->createElementNS(self::SITEMAP_NAMESPACE, 'urlset');
        $document->appendChild($urlset);

        foreach ($this->urls as $entry) {
            $urlElement = $document->createElement('url');

            $locElement = $document->createElement('loc', htmlspecialchars($entry['url'], ENT_XML1));
            $urlElement->appendChild($locElement);

            if ($entry['lastmod'] !== null) {
                $lastmodElement = $document->createElement('lastmod', $entry['lastmod']);
                $urlElement->appendChild($lastmodElement);
            }

            $changefreqElement = $document->createElement('changefreq', $entry['changefreq']);
            $urlElement->appendChild($changefreqElement);

            $priorityElement = $document->createElement('priority', number_format($entry['priority'], 1));
            $urlElement->appendChild($priorityElement);

            $urlset->appendChild($urlElement);
        }

        return $document->saveXML() ?: '';
    }

    public function writeTo(string $path): bool
    {
        $xml = $this->toXml();

        $directory = dirname($path);

        if ($directory !== '.' && ! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return file_put_contents($path, $xml) !== false;
    }
}
