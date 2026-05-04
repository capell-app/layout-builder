<?php

declare(strict_types=1);

use Capell\Migrator\Support\ImportSourceRegistry;
use Capell\WordPressImporter\Services\WxrReader;

it('registers the WordPress WXR reader with migrator', function (): void {
    expect(resolve(ImportSourceRegistry::class)->readerFor('export.xml'))
        ->toBeInstanceOf(WxrReader::class);
});

it('reads WordPress WXR posts and pages into migrator rows', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'capell-wxr-');
    file_put_contents($path, <<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0"
    xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"
    xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:wp="http://wordpress.org/export/1.2/">
    <channel>
        <title>Example WordPress Site</title>
        <wp:wxr_version>1.2</wp:wxr_version>
        <item>
            <title>About</title>
            <link>https://example.test/about/</link>
            <content:encoded><![CDATA[<p>About body</p>]]></content:encoded>
            <excerpt:encoded><![CDATA[About excerpt]]></excerpt:encoded>
            <category domain="category" nicename="company"><![CDATA[Company]]></category>
            <category domain="post_tag" nicename="featured"><![CDATA[Featured]]></category>
            <wp:post_id>10</wp:post_id>
            <wp:post_name>about</wp:post_name>
            <wp:post_type>page</wp:post_type>
            <wp:status>publish</wp:status>
            <wp:post_date>2026-01-01 10:00:00</wp:post_date>
            <wp:post_parent>0</wp:post_parent>
            <dc:creator>ben</dc:creator>
            <wp:attachment_url>https://example.test/about-hero.jpg</wp:attachment_url>
        </item>
        <item>
            <title>Hero image</title>
            <wp:post_type>attachment</wp:post_type>
            <wp:attachment_url>https://example.test/hero.jpg</wp:attachment_url>
        </item>
        <item>
            <title>News</title>
            <link>https://example.test/news/</link>
            <content:encoded><![CDATA[<p>News body</p>]]></content:encoded>
            <wp:post_id>11</wp:post_id>
            <wp:post_name>news</wp:post_name>
            <wp:post_type>post</wp:post_type>
            <wp:status>draft</wp:status>
            <wp:post_parent>10</wp:post_parent>
        </item>
    </channel>
</rss>
XML);

    $result = (new WxrReader)->read($path);

    expect($result->sourceType)->toBe('wordpress-wxr')
        ->and($result->metadata['site_title'])->toBe('Example WordPress Site')
        ->and($result->rows)->toHaveCount(2)
        ->and($result->rows[0]['post_title'])->toBe('About')
        ->and($result->rows[0]['post_content'])->toBe('<p>About body</p>')
        ->and($result->rows[0]['author_login'])->toBe('ben')
        ->and($result->rows[0]['categories'])->toBe(['Company'])
        ->and($result->rows[0]['tags'])->toBe(['Featured'])
        ->and($result->rows[0]['attachments'])->toBe([['url' => 'https://example.test/about-hero.jpg', 'title' => 'About']])
        ->and($result->rows[1]['parent_id'])->toBe('10');
});
