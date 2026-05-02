<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\LanguageFactory;
use Capell\Core\Database\Factories\SiteFactory;
use Capell\Core\Enums\RedirectStatusCodeEnum;
use Capell\Core\Enums\UrlTypeEnum;
use Capell\Core\Models\PageUrl;
use Capell\Redirects\Actions\RefreshRedirectHealthSnapshotAction;
use Capell\Redirects\Models\RedirectHealthSnapshot;

it('stores redirect health for a redirect chain', function (): void {
    $language = LanguageFactory::new()->create();
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();

    $targetRedirect = PageUrl::factory()
        ->site($site)
        ->language($language)
        ->state([
            'url' => '/next',
            'target_url' => '/final',
            'type' => UrlTypeEnum::Redirect,
            'status_code' => RedirectStatusCodeEnum::Permanent,
            'status' => true,
        ])
        ->create();

    $redirect = PageUrl::factory()
        ->site($site)
        ->language($language)
        ->state([
            'url' => '/old',
            'target_url' => '/next',
            'type' => UrlTypeEnum::Redirect,
            'status_code' => RedirectStatusCodeEnum::Permanent,
            'status' => true,
        ])
        ->create();

    $snapshot = RefreshRedirectHealthSnapshotAction::run($redirect);

    expect($snapshot)->toBeInstanceOf(RedirectHealthSnapshot::class)
        ->and($snapshot->page_url_id)->toBe($redirect->id)
        ->and($snapshot->has_chain)->toBeTrue()
        ->and($snapshot->has_loop)->toBeFalse()
        ->and($snapshot->warning_count)->toBeGreaterThan(0)
        ->and($targetRedirect->exists)->toBeTrue();
});

it('does not mark non-chain warnings as redirect chains', function (): void {
    $language = LanguageFactory::new()->create();
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();

    PageUrl::factory()
        ->site($site)
        ->language($language)
        ->state([
            'url' => '/conflicting-source',
            'target_url' => '/automatic-target',
            'type' => UrlTypeEnum::Redirect,
            'is_manual' => false,
            'status_code' => RedirectStatusCodeEnum::Permanent,
            'status' => true,
        ])
        ->create();

    $redirect = PageUrl::factory()
        ->site($site)
        ->language($language)
        ->state([
            'url' => '/conflicting-source',
            'target_url' => '/manual-target',
            'type' => UrlTypeEnum::Redirect,
            'is_manual' => true,
            'status_code' => RedirectStatusCodeEnum::Permanent,
            'status' => true,
        ])
        ->create();

    $snapshot = RefreshRedirectHealthSnapshotAction::run($redirect);

    expect($snapshot)->toBeInstanceOf(RedirectHealthSnapshot::class)
        ->and($snapshot->has_chain)->toBeFalse()
        ->and($snapshot->warning_count)->toBeGreaterThan(0);
});
