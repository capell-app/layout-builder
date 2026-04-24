<?php

declare(strict_types=1);

use Capell\SeoTools\Support\CanonicalUrl;
use Illuminate\Http\Request;

test('strips UTM parameters', function (): void {
    $canonical = new CanonicalUrl('https://example.com/about?utm_source=google&utm_medium=cpc&foo=bar');

    expect($canonical->resolve())->toBe('https://example.com/about?foo=bar');
});

test('removes trailing slashes but preserves root slash', function (): void {
    $withTrailing = new CanonicalUrl('https://example.com/about/');
    expect($withTrailing->resolve())->toBe('https://example.com/about');

    $root = new CanonicalUrl('https://example.com/');
    expect($root->resolve())->toBe('https://example.com/');
});

test('render() produces the correct link tag', function (): void {
    $canonical = new CanonicalUrl('https://example.com/about');

    expect($canonical->render())->toBe('<link rel="canonical" href="https://example.com/about" />');
});

test('works with URL that has no query string', function (): void {
    $canonical = new CanonicalUrl('https://example.com/blog/post-title');

    expect($canonical->resolve())->toBe('https://example.com/blog/post-title');
    expect($canonical->render())->toContain('href="https://example.com/blog/post-title"');
});

test('fromRequest builds from current request URL', function (): void {
    $request = Request::create('https://example.com/page?utm_source=google&page=1');
    $canonical = CanonicalUrl::fromRequest($request);

    expect($canonical->resolve())
        ->toBe('https://example.com/page?page=1');
});
