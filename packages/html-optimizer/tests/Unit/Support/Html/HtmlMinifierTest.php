<?php

declare(strict_types=1);

use Capell\HtmlOptimizer\Support\Html\HtmlMinifier;

it('returns empty string for empty input', function (): void {
    expect((new HtmlMinifier)->minify(''))->toBe('');
});

it('minifies whitespace in html', function (): void {
    $html = <<<'HTML'
        <div>
            <p>Hello World</p>
        </div>
    HTML;

    $result = (new HtmlMinifier)->minify($html);

    expect($result)->not()->toContain("\n")
        ->and($result)->toContain('Hello World');
});

it('preserves attribute and class order', function (): void {
    $html = '<div class="foo bar" id="test">Content</div>';

    $result = (new HtmlMinifier)->minify($html);

    expect($result)->toContain('class="foo bar"')
        ->and($result)->toContain('id="test"');
});

it('does not minify inside pre and code blocks', function (): void {
    $html = '<pre>   keep   spacing  </pre><code>  a   b  </code>';

    $result = (new HtmlMinifier)->minify($html);

    expect($result)->toContain('<pre>   keep   spacing  </pre>')
        ->and($result)->toContain('<code>  a   b  </code>');
});
