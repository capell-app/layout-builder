<?php

declare(strict_types=1);

use Capell\SeoTools\Support\AiResponseParser;

uses()->group('admin-ai');

it('parses numbered list items', function (): void {
    $parser = new AiResponseParser;
    $content = "1. Alpha\n2. Beta\n3. Gamma";

    $items = $parser->parse($content);
    expect($items)->toHaveCount(3);
    expect($items[0]['value'] ?? null)->toBe('Alpha');
    expect($items[1]['value'] ?? null)->toBe('Beta');
    expect($items[2]['value'] ?? null)->toBe('Gamma');
});

it('parses unicode bullet list items', function (): void {
    $parser = new AiResponseParser;
    $content = "• Delta\n• Epsilon\n• Zeta";

    $items = $parser->parse($content);
    expect($items)->toHaveCount(3);
    expect($items[0]['value'] ?? null)->toBe('Delta');
    expect($items[1]['value'] ?? null)->toBe('Epsilon');
    expect($items[2]['value'] ?? null)->toBe('Zeta');
});

it('parses dash bullet list items', function (): void {
    $parser = new AiResponseParser;
    $content = "- One\n- Two\n- Three";

    $items = $parser->parse($content);
    expect($items)->toHaveCount(3);
    expect($items[0]['value'] ?? null)->toBe('One');
    expect($items[1]['value'] ?? null)->toBe('Two');
    expect($items[2]['value'] ?? null)->toBe('Three');
});
