<?php

declare(strict_types=1);

use Capell\SeoTools\Support\AiResponseParser;

it('parses structured JSON responses', function (): void {
    $parser = new AiResponseParser;

    $json = '{"titles":["A","B"]}';
    $parsed = $parser->parse($json);

    expect($parsed)->toHaveKey('titles')
        ->and($parsed['titles'])->toContain('A', 'B');
});

it('throws on malformed input', function (): void {
    $parser = new AiResponseParser;

    $result = $parser->parse('not json');
    expect($result)->toBe([
        [
            'value' => 'not json',
            'source' => 'fallback',
        ],
    ]);
});
