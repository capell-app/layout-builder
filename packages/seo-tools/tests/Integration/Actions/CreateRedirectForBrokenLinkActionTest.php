<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\LanguageFactory;
use Capell\Core\Database\Factories\PageFactory;
use Capell\Core\Database\Factories\SiteFactory;
use Capell\Core\Enums\RedirectStatusCodeEnum;
use Capell\Core\Enums\UrlTypeEnum;
use Capell\Core\Models\PageUrl;
use Capell\SeoTools\Actions\CreateRedirectForBrokenLinkAction;
use Capell\SeoTools\Filament\Actions\CreateRedirectFromBrokenLinkAction;
use Capell\SeoTools\Models\BrokenLink;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

it('creates a manual redirect from a broken link with redirect validation', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    $page = PageFactory::new()->site($site)->withTranslations($language)->create();

    PageUrl::factory()->page($page)->site($site)->language($language)->state(['url' => '/source-page'])->create();

    $brokenLink = BrokenLink::query()->create([
        'page_id' => $page->id,
        'target_url' => '/missing-page',
        'http_status' => 404,
        'last_checked_at' => now(),
    ]);

    $redirect = CreateRedirectForBrokenLinkAction::run(
        brokenLink: $brokenLink,
        sourceUrl: '/missing-page',
        targetUrl: '/replacement-page',
        statusCode: RedirectStatusCodeEnum::Permanent,
        notes: 'Created from SEO broken link report.',
    );

    expect($redirect)->toBeInstanceOf(PageUrl::class)
        ->and($redirect->url)->toBe('/missing-page')
        ->and($redirect->target_url)->toBe('/replacement-page')
        ->and($redirect->type)->toBe(UrlTypeEnum::Redirect)
        ->and($redirect->is_manual)->toBeTrue()
        ->and($redirect->site_id)->toBe($site->id)
        ->and($redirect->language_id)->toBe($language->id);
});

it('rejects invalid redirect targets through the redirects validator', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    $page = PageFactory::new()->site($site)->withTranslations($language)->create();

    PageUrl::factory()->page($page)->site($site)->language($language)->state(['url' => '/source-page'])->create();

    $brokenLink = BrokenLink::query()->create([
        'page_id' => $page->id,
        'target_url' => '/missing-page',
        'http_status' => 404,
        'last_checked_at' => now(),
    ]);

    CreateRedirectForBrokenLinkAction::run(
        brokenLink: $brokenLink,
        sourceUrl: '/missing-page',
        targetUrl: '//evil.example',
        statusCode: RedirectStatusCodeEnum::Permanent,
    );
})->throws(ValidationException::class);

it('links to the redirects create page with broken link context', function (): void {
    Route::get('/admin/redirects')->name('filament.admin.resources.redirects.index');

    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    $page = PageFactory::new()->site($site)->withTranslations($language)->create();

    PageUrl::factory()->page($page)->site($site)->language($language)->state(['url' => '/source-page'])->create();

    $brokenLink = BrokenLink::query()->create([
        'page_id' => $page->id,
        'target_url' => 'https://example.com/missing-page?utm=tracking',
        'http_status' => 404,
        'last_checked_at' => now(),
    ]);

    $url = CreateRedirectFromBrokenLinkAction::make()
        ->record($brokenLink)
        ->getUrl();

    expect($url)
        ->toContain('create_redirect=1')
        ->toContain('url=%2Fmissing-page')
        ->toContain('site_id=' . $site->id)
        ->toContain('language_id=' . $language->id)
        ->toContain('status_code=301');
});
