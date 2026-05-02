<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\SiteDomain;
use Capell\Redirects\Actions\AddRedirectUrlAction;

it('adds a redirect url', function (): void {
    $language = Language::factory()->create();
    $domain = SiteDomain::factory()->create(['language_id' => $language->id]);
    $page = Page::factory()->site($domain->site)->create();

    AddRedirectUrlAction::run($page, $language, '/new');

    expect($page->pageUrls)->toHaveCount(1)
        ->and($page->pageUrls->first()->url)->toBe('/new');
});

it('rejects invalid url', function (): void {
    $page = Page::factory()->create();
    $language = Language::factory()->create();

    expect(fn () => AddRedirectUrlAction::run($page, $language, 'bad'))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects redirects for languages not configured on the page site', function (): void {
    $language = Language::factory()->create();
    $otherLanguage = Language::factory()->create();
    $domain = SiteDomain::factory()->create(['language_id' => $language->id]);
    $page = Page::factory()->site($domain->site)->create();

    expect(fn () => AddRedirectUrlAction::run($page, $otherLanguage, '/new'))
        ->toThrow(InvalidArgumentException::class);
});
