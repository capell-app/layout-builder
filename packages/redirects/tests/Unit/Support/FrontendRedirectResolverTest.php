<?php

declare(strict_types=1);

use Capell\Core\Enums\RedirectStatusCodeEnum;
use Capell\Core\Enums\UrlTypeEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Frontend\Data\RedirectDecisionData as FrontendRedirectDecisionData;
use Capell\Redirects\Contracts\RedirectRecorder;
use Capell\Redirects\Support\FrontendRedirectResolver;
use Capell\Redirects\Support\PageUrlRedirectResolver;

it('returns a frontend redirect decision for an exact active source URL', function (): void {
    $site = Site::factory()->create();
    $language = Language::factory()->create();

    $redirect = PageUrl::factory()
        ->site($site)
        ->language($language)
        ->state([
            'url' => '/legacy-page',
            'target_url' => '/current-page',
            'type' => UrlTypeEnum::Redirect,
            'status_code' => RedirectStatusCodeEnum::Temporary,
            'status' => true,
        ])
        ->create();

    PageUrl::factory()
        ->site($site)
        ->language($language)
        ->state([
            'url' => '/legacy-page/nested',
            'target_url' => '/wrong-page',
            'type' => UrlTypeEnum::Redirect,
            'status_code' => RedirectStatusCodeEnum::Permanent,
            'status' => true,
        ])
        ->create();

    $resolver = new FrontendRedirectResolver(
        new PageUrlRedirectResolver(resolve(RedirectRecorder::class)),
    );

    $decision = $resolver->resolve($site, $language, '/legacy-page');

    expect($decision)->toBeInstanceOf(FrontendRedirectDecisionData::class)
        ->and($decision->targetUrl)->toBe('/current-page')
        ->and($decision->statusCode)->toBe(302)
        ->and($redirect->refresh()->hit_count)->toBe(1);
});

it('ignores inactive redirect records during frontend resolution', function (): void {
    $site = Site::factory()->create();
    $language = Language::factory()->create();

    $inactiveRedirect = PageUrl::factory()
        ->site($site)
        ->language($language)
        ->state([
            'url' => '/disabled-page',
            'target_url' => '/current-page',
            'type' => UrlTypeEnum::Redirect,
            'status_code' => RedirectStatusCodeEnum::Permanent,
            'status' => false,
        ])
        ->create();

    $resolver = new FrontendRedirectResolver(
        new PageUrlRedirectResolver(resolve(RedirectRecorder::class)),
    );

    expect($resolver->resolve($site, $language, '/disabled-page'))->toBeNull()
        ->and($inactiveRedirect->refresh()->hit_count)->toBe(0);
});
