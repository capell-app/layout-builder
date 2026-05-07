<?php

declare(strict_types=1);

use Capell\Core\Actions\PageSavedAction;
use Capell\Core\Actions\SiteCreatedAction;
use Capell\Core\Events\PageDeleted;
use Capell\Core\Events\PageSaved;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Listeners\Sitemap\RegenerateSitemapsOnPageDeleted;
use Capell\SeoSuite\Listeners\Sitemap\RegenerateSitemapsOnPageSaved;
use Capell\SeoSuite\Support\Sitemap\XmlSitemapGenerator;

function seoSuiteLifecycleFakeXmlSitemapGenerator(): XmlSitemapGenerator
{
    return new class extends XmlSitemapGenerator
    {
        /** @var list<int> */
        public array $incrementalSiteIds = [];

        public function processIncremental(
            Site $site,
            ?Closure $start = null,
            ?Closure $prepare = null,
            ?Closure $checkpoint = null,
            ?Closure $end = null,
        ): void {
            $this->incrementalSiteIds[] = (int) $site->id;
        }
    };
}

it('regenerates the owning site sitemap when a page is saved', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $page = Page::factory()->site($site)->withTranslations()->create();

    $generator = seoSuiteLifecycleFakeXmlSitemapGenerator();
    app()->instance(XmlSitemapGenerator::class, $generator);

    PageSavedAction::run($page, ['title' => 'Updated title']);

    expect($generator->incrementalSiteIds)->toBe([(int) $site->id]);
});

it('regenerates the owning site sitemap when a page is deleted', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $page = Page::factory()->site($site)->withTranslations()->create();

    $generator = seoSuiteLifecycleFakeXmlSitemapGenerator();
    app()->instance(XmlSitemapGenerator::class, $generator);

    event(new PageDeleted($page, ['reason' => 'admin cleanup']));

    expect($generator->incrementalSiteIds)->toBe([(int) $site->id]);
});

it('regenerates the new site sitemap after site creation workflow runs', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->create();

    $generator = seoSuiteLifecycleFakeXmlSitemapGenerator();
    app()->instance(XmlSitemapGenerator::class, $generator);

    SiteCreatedAction::run($site, [
        'name' => 'Example Site',
        'language_id' => $language->id,
    ]);

    expect($generator->incrementalSiteIds)->toBe([(int) $site->id]);
});

it('ignores saved page events when the page has no owning site', function (): void {
    $generator = seoSuiteLifecycleFakeXmlSitemapGenerator();
    $listener = new RegenerateSitemapsOnPageSaved($generator);

    $listener->handle(new PageSaved(new Page));

    expect($generator->incrementalSiteIds)->toBe([]);
});

it('ignores deleted page events when the page has no owning site', function (): void {
    $generator = seoSuiteLifecycleFakeXmlSitemapGenerator();
    $listener = new RegenerateSitemapsOnPageDeleted($generator);

    $listener->handle(new PageDeleted(new Page));

    expect($generator->incrementalSiteIds)->toBe([]);
});
