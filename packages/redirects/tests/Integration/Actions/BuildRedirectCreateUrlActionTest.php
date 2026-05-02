<?php

declare(strict_types=1);

use Capell\Core\Enums\RedirectStatusCodeEnum;
use Capell\Redirects\Actions\BuildRedirectCreateUrlAction;
use Illuminate\Support\Facades\Route;

it('builds a redirect create URL with prefilled query values', function (): void {
    Route::get('/admin/redirects')->name('filament.admin.resources.redirects.index');

    $url = BuildRedirectCreateUrlAction::run(
        sourceUrl: '/missing-page',
        targetUrl: '/replacement-page',
        siteId: 10,
        languageId: 20,
        statusCode: RedirectStatusCodeEnum::Permanent,
    );

    expect($url)
        ->toContain('create_redirect=1')
        ->toContain('url=%2Fmissing-page')
        ->toContain('target_url=%2Freplacement-page')
        ->toContain('site_id=10')
        ->toContain('language_id=20')
        ->toContain('status_code=301');
});
