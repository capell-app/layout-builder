<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\LanguageFactory;
use Capell\Core\Database\Factories\PageFactory;
use Capell\Core\Database\Factories\SiteFactory;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoTools\Actions\PageMetaSchemaAction;

it('generates correct schema for a simple page', function (): void {
    $language = LanguageFactory::new()->create([
        'name' => 'English',
        'code' => 'en',
    ]);

    $site = Site::factory()
        ->recycle($language)
        ->language($language)
        ->hasSiteDomain()
        ->create();

    // Set up a non-root page to ensure breadcrumbs are included
    $parentPage = Page::factory()
        ->recycle($language)
        ->site($site)
        ->withTranslations()
        ->create();

    $page = Page::factory()
        ->recycle($language)
        ->site($site)
        ->parent($parentPage)
        ->withTranslations()
        ->create();

    $configurator = PageMetaSchemaAction::run($page, $site, $language);

    expect($configurator)
        ->toHaveKey('@context', 'https://schema.org')
        ->toHaveKey('@type', 'WebPage')
        ->toHaveKey('name', $page->translation->label)
        ->toHaveKey('headline', $page->translation->title)
        ->toHaveKey('url', $page->pageUrl?->full_url)
        ->toHaveKey('breadcrumb');
});

it('builds missing optional fields gracefully', function (): void {
    $language = LanguageFactory::new()->create([
        'name' => 'French',
        'code' => 'fr',
    ]);

    $site = SiteFactory::new()
        ->recycle($language)
        ->language($language)
        ->hasSiteDomain()
        ->create();

    $page = PageFactory::new()
        ->recycle($language)
        ->site($site)
        ->withTranslations()
        ->state([
            // Leave visible_from null so datePublished is omitted from the schema.
            'visible_from' => null,
            'visible_until' => null,
            'created_by' => null,
        ])
        ->create();

    $page->refresh();

    $site->refresh();

    $configurator = PageMetaSchemaAction::run($page, $site, $language);

    expect($configurator)
        ->not()->toHaveKey('datePublished')
        ->not()->toHaveKey('creator');
});

it('includes all available languages in schema', function (): void {
    $language1 = LanguageFactory::new()->create([
        'name' => 'English',
        'code' => 'en',
    ]);
    $language2 = LanguageFactory::new()->create([
        'name' => 'German',
        'code' => 'de',
    ]);

    $site = SiteFactory::new()
        ->recycle($language1)
        ->language($language1)
        ->hasSiteDomain()
        ->create();

    $page = PageFactory::new()
        ->site($site)
        ->withTranslations(
            [$language1, $language2],
            [
                $language1->id => ['title' => 'English Title'],
                $language2->id => ['title' => 'German Title'],
            ],
        )
        ->create();

    $page->refresh();

    $site->refresh();

    $configurator = PageMetaSchemaAction::run($page, $site, $language1);

    expect($configurator['availableLanguage'])
        ->toContain('English')
        ->toContain('German');
});

it('generates schema with custom type from type meta', function (): void {
    $language = LanguageFactory::new()->create([
        'name' => 'English',
        'code' => 'en',
    ]);
    $site = SiteFactory::new()->recycle($language)->language($language)->hasSiteDomain()->create();
    $page = PageFactory::new()->site($site)->withTranslations($language)->create();
    $page->type->meta = ['schema' => ['type' => 'Article']];
    $page->type->save();
    $page->refresh();

    $site->refresh();
    $configurator = PageMetaSchemaAction::run($page, $site, $language);

    expect($configurator)->toHaveKey('@type', 'Article');
});

it('builds missing creator, dates, and meta fields', function (): void {
    $language = LanguageFactory::new()->create([
        'name' => 'English',
        'code' => 'en',
    ]);
    $site = SiteFactory::new()->recycle($language)->language($language)->hasSiteDomain()->create();
    $page = PageFactory::new()->site($site)->withTranslations($language)->create([
        'created_at' => null,
        'updated_at' => null,
        'visible_from' => null,
    ]);
    $page->setRelation('creator', null);
    $page->refresh();

    $site->refresh();
    $configurator = PageMetaSchemaAction::run($page, $site, $language);

    expect($configurator)
        ->not()->toHaveKey('dateCreated')
        ->not()->toHaveKey('dateModified')
        ->not()->toHaveKey('datePublished')
        ->not()->toHaveKey('creator');
});

it('builds availableLanguage with multiple translations', function (): void {
    $language1 = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $language2 = LanguageFactory::new()->create(['name' => 'French', 'code' => 'fr']);
    $site = SiteFactory::new()->recycle($language1)->language($language1)->withTranslations([$language1, $language2])->create();
    $page = PageFactory::new()->site($site)->withTranslations([$language1, $language2])->create();
    $page->refresh();

    $site->refresh();
    $configurator = PageMetaSchemaAction::run($page, $site, $language1);

    expect($configurator['availableLanguage'])
        ->toContain('English')
        ->toContain('French');
});

it('builds keywords, summary, and description fallback', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->hasSiteDomain()->create();
    $page = PageFactory::new()->site($site)->withTranslations($language, ['meta' => ['keywords' => 'foo,bar', 'description' => 'desc']])->create();

    $page->refresh();

    $site->refresh();
    $configurator = PageMetaSchemaAction::run($page, $site, $language);

    expect($configurator)->toHaveKey('keywords', 'foo,bar');

    // Remove summary, fallback to meta description
    $page->translation->meta = ['description' => 'desc'];
    // Do not save, just call schema builder
    $configurator = PageMetaSchemaAction::run($page, $site, $language);

    expect($configurator['description'])->toContain('desc');
});
