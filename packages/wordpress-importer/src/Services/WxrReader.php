<?php

declare(strict_types=1);

namespace Capell\WordPressImporter\Services;

use Capell\MigrationAssistant\Contracts\ImportSourceReader;
use Capell\MigrationAssistant\Data\ExternalImportReadResult;
use RuntimeException;
use SimpleXMLElement;

final class WxrReader implements ImportSourceReader
{
    public function supports(string $extension): bool
    {
        return strtolower($extension) === 'xml';
    }

    public function read(string $path): ExternalImportReadResult
    {
        if (! is_readable($path)) {
            throw new RuntimeException(sprintf('WordPress export [%s] is not readable.', $path));
        }

        $xml = simplexml_load_file($path, SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET);
        if (! $xml instanceof SimpleXMLElement) {
            throw new RuntimeException(sprintf('WordPress export [%s] could not be parsed.', $path));
        }

        $channel = $xml->channel;
        throw_if(! $channel instanceof SimpleXMLElement || (! property_exists($channel, 'item') || $channel->item === null), RuntimeException::class, 'WordPress export must contain a channel with item entries.');

        $rows = [];
        foreach ($channel->item as $item) {
            $wp = $item->children('wp', true);
            $postType = trim((string) $wp->post_type);

            if (! in_array($postType, ['page', 'post'], true)) {
                continue;
            }

            $rows[] = $this->rowFromItem($item);
        }

        return new ExternalImportReadResult(
            sourceType: 'wordpress-wxr',
            columns: $this->columnsFor($rows),
            rows: $rows,
            metadata: [
                'filename' => basename($path),
                'site_title' => trim((string) $channel->title),
                'wxr_version' => trim((string) $channel->children('wp', true)->wxr_version),
            ],
            suggestedTarget: 'page',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function rowFromItem(SimpleXMLElement $item): array
    {
        $wp = $item->children('wp', true);
        $content = $item->children('content', true);
        $dc = $item->children('dc', true);
        $excerpt = $item->children('excerpt', true);

        return [
            'post_id' => trim((string) $wp->post_id),
            'post_type' => trim((string) $wp->post_type),
            'post_title' => trim((string) $item->title),
            'post_name' => trim((string) $wp->post_name),
            'link' => trim((string) $item->link),
            'post_content' => trim((string) $content->encoded),
            'post_excerpt' => trim((string) $excerpt->encoded),
            'post_status' => trim((string) $wp->status),
            'post_date' => trim((string) $wp->post_date),
            'parent_id' => trim((string) $wp->post_parent),
            'author_login' => trim((string) $dc->creator),
            'categories' => $this->terms($item, 'category'),
            'tags' => $this->terms($item, 'post_tag'),
            'attachments' => $this->attachments($wp, trim((string) $item->title)),
        ];
    }

    /**
     * @return list<string>
     */
    private function terms(SimpleXMLElement $item, string $domain): array
    {
        $terms = [];

        foreach ($item->category as $category) {
            $attributes = $category->attributes();
            if ((string) ($attributes['domain'] ?? '') !== $domain) {
                continue;
            }

            $terms[] = trim((string) $category);
        }

        return array_values(array_filter(
            $terms,
            static fn (string $term): bool => $term !== '',
        ));
    }

    /**
     * @return list<array{url: string, title: string}>
     */
    private function attachments(SimpleXMLElement $wp, string $title): array
    {
        $url = trim((string) $wp->attachment_url);
        if ($url === '') {
            return [];
        }

        return [[
            'url' => $url,
            'title' => $title,
        ]];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<string>
     */
    private function columnsFor(array $rows): array
    {
        return array_values(array_unique(array_merge(...array_map(array_keys(...), $rows !== [] ? $rows : [[]]))));
    }
}
