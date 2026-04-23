<?php

declare(strict_types=1);

use Capell\SeoTools\Support\AiResponseParser;

uses()->group('admin-ai');

it('parses bullet list content into normalized items', function (): void {
    $parser = new AiResponseParser;

    $content = "- First suggestion\n- Second suggestion\n- Third";

    $items = $parser->parse($content);

    expect($items)->toBeArray();
    expect($items)->toHaveCount(3);

    foreach ($items as $item) {
        expect($item)->toBeArray();
        expect($item['value'] ?? null)->toBeString();
        expect($item['source'] ?? null)->toBe('list');
        expect($item['length'] ?? null)->toBeInt();
    }
});

it('parses JSON array of strings into normalized items', function (): void {
    $parser = new AiResponseParser;

    $content = json_encode(['Alpha', 'Beta', 'Gamma'], JSON_THROW_ON_ERROR);

    $items = $parser->parse($content);

    expect($items)->toBeArray();
    expect($items)->toHaveCount(3);
    expect($items[0]['value'] ?? null)->toBe('Alpha');
    expect($items[0]['source'] ?? null)->toBe('string');
});

it('parses JSON array of objects with title/reason fields', function (): void {
    $parser = new AiResponseParser;

    $content = json_encode([
        ['title' => 'Alpha', 'reason' => 'Short and clear'],
        ['name' => 'Beta', 'description' => 'Describes content'],
        ['value' => 'Gamma', 'explanation' => 'Alternative wording'],
    ], JSON_THROW_ON_ERROR);

    $items = $parser->parse($content);

    expect($items)->toBeArray();
    expect($items)->toHaveCount(3);

    expect($items[0]['value'] ?? null)->toBe('Alpha');
    expect($items[0]['reason'] ?? null)->toBe('Short and clear');
    expect($items[0]['source'] ?? null)->toBe('json');

    expect($items[1]['value'] ?? null)->toBe('Beta');
    expect($items[1]['reason'] ?? null)->toBe('Describes content');

    expect($items[2]['value'] ?? null)->toBe('Gamma');
    expect($items[2]['reason'] ?? null)->toBe('Alternative wording');
});
