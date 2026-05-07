<?php

declare(strict_types=1);

use Capell\SeoSuite\Support\SocialCards;

it('builds open graph and twitter tag arrays with optional media fields', function (): void {
    $cards = new SocialCards(
        title: 'Capell Launch',
        description: 'A faster CMS workflow',
        url: 'https://example.com/launch',
        image: 'https://example.com/social.jpg',
        type: 'article',
        siteName: 'Capell',
        twitterCard: 'summary',
        twitterSite: '@capellcms',
    );

    expect($cards->ogTags())->toBe([
        'og:title' => 'Capell Launch',
        'og:description' => 'A faster CMS workflow',
        'og:url' => 'https://example.com/launch',
        'og:type' => 'article',
        'og:image' => 'https://example.com/social.jpg',
        'og:site_name' => 'Capell',
    ])
        ->and($cards->twitterTags())->toBe([
            'twitter:card' => 'summary',
            'twitter:title' => 'Capell Launch',
            'twitter:description' => 'A faster CMS workflow',
            'twitter:image' => 'https://example.com/social.jpg',
            'twitter:site' => '@capellcms',
        ]);
});

it('omits optional empty social card fields', function (): void {
    $cards = new SocialCards(title: 'Plain Page');

    expect($cards->ogTags())->toBe([
        'og:title' => 'Plain Page',
        'og:description' => '',
        'og:url' => '',
        'og:type' => 'website',
    ])
        ->and($cards->twitterTags())->toBe([
            'twitter:card' => 'summary_large_image',
            'twitter:title' => 'Plain Page',
            'twitter:description' => '',
        ]);
});

it('renders escaped meta tags for social cards', function (): void {
    $html = (new SocialCards(
        title: 'Tom & "Jerry"',
        description: '<script>alert("x")</script>',
        url: 'https://example.com/?name=Tom&quote="yes"',
        image: 'https://example.com/image.jpg',
    ))->render();

    expect($html)->toContain('<meta property="og:title" content="Tom &amp; &quot;Jerry&quot;">')
        ->and($html)->toContain('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;')
        ->and($html)->toContain('https://example.com/?name=Tom&amp;quote=&quot;yes&quot;')
        ->and($html)->toContain('<meta name="twitter:image" content="https://example.com/image.jpg">');
});
