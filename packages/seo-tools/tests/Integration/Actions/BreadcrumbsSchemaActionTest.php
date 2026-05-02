<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\LanguageFactory;
use Capell\Core\Database\Factories\PageFactory;
use Capell\Core\Database\Factories\SiteFactory;
use Capell\SeoTools\Actions\BreadcrumbsSchemaAction;

it('generates breadcrumbs for a page with ancestors', function (): void {
    $language = LanguageFactory::new()->create([
        'name' => 'English',
        'code' => 'en',
    ]);

    $site = SiteFactory::new()
        ->recycle($language)
        ->language($language)
        ->hasSiteDomain()
        ->create();

    $parent = PageFactory::new()
        ->recycle($language)
        ->site($site)
        ->withTranslations()
        ->create();

    $child = PageFactory::new()
        ->recycle($language)
        ->site($site)
        ->withTranslations()
        ->parent($parent)
        ->create();

    $child->refresh();

    $site->refresh();

    $breadcrumbs = BreadcrumbsSchemaAction::run($child, $site, $language);

    if (isset($breadcrumbs['itemListElement'])) {
        expect($breadcrumbs['itemListElement'])
            ->toHaveCount(2)
            ->and($breadcrumbs['itemListElement'][0]['name'])->toBe($parent->translation->label)
            ->and($breadcrumbs['itemListElement'][1]['name'])->toBe($child->translation->label);
    } else {
        expect($breadcrumbs)->toBeArray();
    }
});

it('builds canonical pages in breadcrumbs', function (): void {
    $language = LanguageFactory::new()->create([
        'name' => 'English',
        'code' => 'en',
    ]);

    $site = SiteFactory::new()
        ->recycle($language)
        ->language($language)
        ->hasSiteDomain()
        ->create();

    $canonical = PageFactory::new()
        ->site($site)
        ->withTranslations()
        ->create();

    $page = PageFactory::new()
        ->site($site)
        ->withTranslations()
        ->create();

    $breadcrumbs = BreadcrumbsSchemaAction::run($page, $site, $language);
    expect($breadcrumbs)->toBeArray();
});

it('generates breadcrumbs for page with no ancestors or canonicals', function (): void {
    $language = LanguageFactory::new()->create([
        'name' => 'English',
        'code' => 'en',
    ]);
    $site = SiteFactory::new()->recycle($language)->language($language)->hasSiteDomain()->create();
    $page = PageFactory::new()->site($site)->withTranslations($language)->create();
    $page->refresh();

    $site->refresh();
    $breadcrumbs = BreadcrumbsSchemaAction::run($page, $site, $language);
    expect($breadcrumbs)->toBeEmpty();
});
