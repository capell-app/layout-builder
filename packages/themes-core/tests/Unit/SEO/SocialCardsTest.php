<?php

declare(strict_types=1);

use Capell\SeoTools\Support\SocialCards;

test('render() includes og:title and og:description', function (): void {
    $cards = new SocialCards(
        title: 'My Page Title',
        description: 'Page description here',
        url: 'https://example.com/page',
        image: 'https://example.com/og-image.jpg',
    );

    $rendered = $cards->render();

    expect($rendered)
        ->toContain('property="og:title" content="My Page Title"')
        ->toContain('property="og:description" content="Page description here"');
});

test('twitterTags() returns the correct keys', function (): void {
    $cards = new SocialCards(title: 'Test', image: 'https://example.com/img.jpg', twitterSite: '@mysite');

    $twitter = $cards->twitterTags();

    expect($twitter)->toHaveKeys(['twitter:card', 'twitter:title', 'twitter:description', 'twitter:image', 'twitter:site']);
    expect($twitter['twitter:card'])->toBe('summary_large_image');
    expect($twitter['twitter:site'])->toBe('@mysite');
});

test('ogTags() returns the right values', function (): void {
    $cards = new SocialCards(
        title: 'Home',
        description: 'Welcome',
        url: 'https://example.com',
        image: 'https://example.com/img.jpg',
        type: 'website',
        siteName: 'Example Site',
    );

    $og = $cards->ogTags();

    expect($og['og:title'])->toBe('Home');
    expect($og['og:description'])->toBe('Welcome');
    expect($og['og:url'])->toBe('https://example.com');
    expect($og['og:image'])->toBe('https://example.com/img.jpg');
    expect($og['og:type'])->toBe('website');
    expect($og['og:site_name'])->toBe('Example Site');
});

test('render() HTML-escapes special characters', function (): void {
    $cards = new SocialCards(
        title: 'A & B < C > D "test"',
        description: "It's a test",
    );

    $rendered = $cards->render();

    expect($rendered)->toContain('A &amp; B &lt; C &gt; D &quot;test&quot;');
    expect($rendered)->toContain('It&apos;s a test');
});

test('render() omits og:image and twitter:image when image is empty', function (): void {
    $cards = new SocialCards(
        title: 'No Image Page',
        description: 'A page without a social image',
        url: 'https://example.com/no-image',
    );

    $rendered = $cards->render();

    expect($rendered)
        ->not->toContain('og:image')
        ->not->toContain('twitter:image');
});

test('twitter:site is only included when twitterSite is set', function (): void {
    $withoutSite = new SocialCards(title: 'Test');
    expect($withoutSite->twitterTags())->not->toHaveKey('twitter:site');
    expect($withoutSite->render())->not->toContain('twitter:site');

    $withSite = new SocialCards(title: 'Test', twitterSite: '@handle');
    expect($withSite->twitterTags())->toHaveKey('twitter:site');
    expect($withSite->render())->toContain('twitter:site');
});
