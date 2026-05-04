<?php

declare(strict_types=1);

use Capell\Migrator\Services\Import\CsvReader;
use Capell\Migrator\Services\Import\ExternalImportPreviewBuilder;
use Capell\Migrator\Services\Import\FieldMapper;
use Capell\Migrator\Services\Import\XmlReader;

it('reads CSV imports into source rows', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'capell-migrator-csv-');
    file_put_contents($path, "title,slug,content\nAbout,about,Hello world\n");

    $result = (new CsvReader)->read($path);

    expect($result->sourceType)->toBe('csv')
        ->and($result->columns)->toBe(['title', 'slug', 'content'])
        ->and($result->rows)->toBe([
            ['title' => 'About', 'slug' => 'about', 'content' => 'Hello world'],
        ]);
});

it('reads XML imports into flattened source rows', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'capell-migrator-xml-');
    file_put_contents($path, <<<'XML'
<?xml version="1.0"?>
<items>
    <item><title>About</title><meta><slug>about</slug></meta></item>
    <item><title>Contact</title><meta><slug>contact</slug></meta></item>
</items>
XML);

    $result = (new XmlReader)->read($path);

    expect($result->sourceType)->toBe('xml')
        ->and($result->columns)->toContain('title', 'meta.slug')
        ->and($result->rows[0]['title'])->toBe('About')
        ->and($result->rows[0]['meta.slug'])->toBe('about');
});

it('maps external rows and builds a preview summary', function (): void {
    $mapped = (new FieldMapper)->map([
        'post_title' => 'Imported page',
        'post_content' => '<p>Hello</p>',
    ]);

    expect($mapped['name'])->toBe('Imported page')
        ->and($mapped['meta']['content'])->toBe('<p>Hello</p>');

    $path = tempnam(sys_get_temp_dir(), 'capell-migrator-preview-');
    file_put_contents($path, "title,content\nPreview,Body\n");

    $preview = (new ExternalImportPreviewBuilder)->build((new CsvReader)->read($path));

    expect($preview->creates)->toBe(1)
        ->and($preview->errors)->toBe([])
        ->and($preview->rows[0]['attributes']['name'])->toBe('Preview');
});
