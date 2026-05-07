<?php

declare(strict_types=1);

use Capell\SeoSuite\Support\CanonicalUrl;
use Illuminate\Http\Request;

it('removes tracking parameters and normalizes trailing slashes', function (): void {
    $canonicalUrl = new CanonicalUrl('https://example.com/articles/?utm_source=newsletter&page=2&fbclid=abc');

    expect($canonicalUrl->resolve())->toBe('https://example.com/articles?page=2');
});

it('keeps configured query parameters and strips custom noise parameters', function (): void {
    $canonicalUrl = new CanonicalUrl(
        'https://example.com/search/?q=capell&preview=true&page=1',
        stripParams: ['preview'],
    );

    expect($canonicalUrl->resolve())->toBe('https://example.com/search?q=capell&page=1');
});

it('resolves canonical URLs from requests', function (): void {
    $request = Request::create('https://example.com/docs/?utm_campaign=spring&section=intro');

    expect(CanonicalUrl::fromRequest($request)->resolve())->toBe('https://example.com/docs?section=intro');
});

it('renders escaped canonical link markup', function (): void {
    $html = (new CanonicalUrl('https://example.com/docs?title=Tom%20%26%20Jerry'))->render();

    expect($html)->toBe('<link rel="canonical" href="https://example.com/docs?title=Tom+%26+Jerry" />');
});

it('returns malformed URLs unchanged', function (): void {
    $url = 'http:///example.com';

    expect((new CanonicalUrl($url))->resolve())->toBe($url);
});
