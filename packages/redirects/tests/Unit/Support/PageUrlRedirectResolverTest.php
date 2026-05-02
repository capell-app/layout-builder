<?php

declare(strict_types=1);

use Capell\Core\Enums\RedirectStatusCodeEnum;
use Capell\Core\Enums\UrlTypeEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Redirects\Contracts\RedirectRecorder;
use Capell\Redirects\Data\RedirectDecisionData;
use Capell\Redirects\Support\PageUrlRedirectResolver;

it('returns manual target urls with configured status code', function (): void {
    $site = Site::factory()->create();
    $language = Language::factory()->create();
    $redirect = PageUrl::factory()
        ->manualRedirect()
        ->site($site)
        ->language($language)
        ->state([
            'url' => '/old',
            'target_url' => '/new',
            'status_code' => RedirectStatusCodeEnum::Temporary,
        ])
        ->create();

    $resolver = new PageUrlRedirectResolver(resolve(RedirectRecorder::class));

    $decision = $resolver->resolve($site, $language, '/old', pageUrl: $redirect);

    expect($decision)->toBeInstanceOf(RedirectDecisionData::class)
        ->and($decision->targetUrl)->toBe('/new')
        ->and($decision->statusCode)->toBe(302)
        ->and($redirect->refresh()->hit_count)->toBe(1);
});

it('returns current page url for automatic redirects', function (): void {
    $site = Site::factory()->create();
    $language = Language::factory()->create();
    $page = Page::factory()->site($site)->create();
    PageUrl::factory()
        ->page($page)
        ->site($site)
        ->language($language)
        ->state(['url' => '/new'])
        ->create();
    $redirect = PageUrl::factory()
        ->page($page)
        ->site($site)
        ->language($language)
        ->type(UrlTypeEnum::Redirect)
        ->state(['url' => '/old'])
        ->create();

    $resolver = new PageUrlRedirectResolver(resolve(RedirectRecorder::class));

    $decision = $resolver->resolve($site, $language, '/old', pageUrl: $redirect);

    expect($decision)->toBeInstanceOf(RedirectDecisionData::class)
        ->and($decision->targetUrl)->toBe('/new')
        ->and($decision->statusCode)->toBe(301)
        ->and($redirect->refresh()->hit_count)->toBe(1);
});

it('returns null for non redirect page urls', function (): void {
    $site = Site::factory()->create();
    $language = Language::factory()->create();
    $page = Page::factory()->site($site)->create();
    $pageUrl = PageUrl::factory()
        ->page($page)
        ->site($site)
        ->language($language)
        ->state(['url' => '/page'])
        ->create();

    $resolver = new PageUrlRedirectResolver(resolve(RedirectRecorder::class));

    expect($resolver->resolve($site, $language, '/page', pageUrl: $pageUrl))->toBeNull()
        ->and($pageUrl->refresh()->hit_count)->toBe(0);
});
