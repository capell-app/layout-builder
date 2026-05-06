<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Support;

use DOMDocument;
use Illuminate\Database\ConnectionInterface;

class SitemapGenerator
{
    private const VALID_CHANGEFREQ = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];

    private const SITEMAP_NAMESPACE = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    /** @var array<int, array{url: string, lastmod: string|null, changefreq: string, priority: float}> */
    private array $urls = [];

    /**
     * Build a SitemapGenerator by scanning a table for slug entries.
     *
     * @param  string  $baseUrl  Scheme + host prefix, no trailing slash
     * @param  string  $slugColumn  Column containing the URL slug
     * @param  string|null  $updatedAtColumn  Column with last-modified datetime; pass null to omit lastmod
     */
    public static function fromTable(
        ConnectionInterface $db,
        string $table,
        string $baseUrl,
        string $slugColumn = 'slug',
        ?string $updatedAtColumn = 'updated_at',
        string $changefreq = 'weekly',
        float $priority = 0.8,
    ): self {
        $generator = new self;
        $baseUrl = rtrim($baseUrl, '/');
        $columns = array_values(array_filter([$slugColumn, $updatedAtColumn], static fn (?string $value): bool => $value !== null));

        $rows = $db->table($table)->get($columns);

        foreach ($rows as $row) {
            $slug = (string) ($row->{$slugColumn} ?? '');
            $rawDate = $updatedAtColumn ? (string) ($row->{$updatedAtColumn} ?? '') : null;
            $lastmod = ($rawDate !== null && $rawDate !== '')
                ? substr($rawDate, 0, 10)
                : null;

            $generator->add(
                url: $baseUrl . '/' . ltrim($slug, '/'),
                lastmod: $lastmod,
                changefreq: $changefreq,
                priority: $priority,
            );
        }

        return $generator;
    }

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

        return $document->saveXML() ?? '';
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
