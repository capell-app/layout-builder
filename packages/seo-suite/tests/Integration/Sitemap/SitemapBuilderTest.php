<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Type;
use Capell\SeoSuite\Contracts\Sitemapable;
use Capell\SeoSuite\Data\SitemapPageData;
use Capell\SeoSuite\Support\Sitemap\SitemapBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Date::setTestNow(Date::create(2024, 1, 1, 0, 0, 0));
    Cache::flush();
});

describe('SitemapBuilder', function (): void {
    it('discovers public pages from real models and returns a structured sitemap tree', function (): void {
        $language = Language::factory()->state(['locale' => 'en'])->create();
        $siteDomain = SiteDomain::factory()
            ->language($language)
            ->state(['scheme' => 'https', 'domain' => 'example.com', 'path' => null])
            ->create();

        $pageType = Type::factory()->page()->create([
            'meta' => ['listable' => true, 'sitemap' => true],
        ]);

        $homepage = Page::factory()
            ->site($siteDomain->site)
            ->type($pageType)
            ->home()
            ->state([
                'created_at' => Date::now(),
                'updated_at' => Date::now(),
                'meta' => [
                    'priority' => 1.0,
                    'cache_time' => 'daily',
                ],
            ])
            ->withTranslations($language, ['title' => 'Home'])
            ->create();

        $parentPage = Page::factory()
            ->site($siteDomain->site)
            ->type($pageType)
            ->state([
                'name' => 'Parent',
                'created_at' => Date::now(),
                'updated_at' => Date::now(),
                'meta' => [
                    'priority' => 0.6,
                    'cache_time' => 'weekly',
                ],
            ])
            ->withTranslations($language, ['title' => 'Parent'])
            ->create();

        $childPage = Page::factory()
            ->site($siteDomain->site)
            ->type($pageType)
            ->parent($parentPage)
            ->state(['name' => 'Child'])
            ->withTranslations($language, ['title' => 'Child'])
            ->create();

        Page::factory()
            ->site($siteDomain->site)
            ->type($pageType)
            ->state(['name' => 'Hidden'])
            ->meta('hidden', true)
            ->withTranslations($language, ['title' => 'Hidden'])
            ->create();

        Cache::flush();

        $builder = new SitemapBuilder(site: $siteDomain->site, domain: $siteDomain, language: $language);
        $result = $builder->build();

        $homepageNode = $result->first(fn (SitemapPageData $page): bool => $page->pageId === $homepage->id);
        $parentNode = $result->first(fn (SitemapPageData $page): bool => $page->pageId === $parentPage->id);

        expect($result)->toHaveCount(2)
            ->and($result->pluck('pageId')->all())->toBe([$homepage->id, $parentPage->id])
            ->and($result->pluck('pageId')->all())->not->toContain($childPage->id)
            ->and($result->pluck('label')->all())->not->toContain('Hidden')
            ->and($homepageNode)->toBeInstanceOf(SitemapPageData::class)
            ->and($homepageNode->url)->toBe($homepage->pageUrl->full_url)
            ->and($homepageNode->changeFrequency)->toBe('daily')
            ->and($homepageNode->priority)->toBe(1.0)
            ->and($homepageNode->lastModified?->format(DATE_ATOM))->toBe(Date::now()->format(DATE_ATOM))
            ->and($parentNode)->toBeInstanceOf(SitemapPageData::class)
            ->and($parentNode->children)->toHaveCount(1)
            ->and($parentNode->children->first())->toBeInstanceOf(SitemapPageData::class)
            ->and($parentNode->children->first()->pageId)->toBe($childPage->id)
            ->and($parentNode->children->first()->url)->toBe($childPage->pageUrl->full_url);
    });

    it('does not expose editor URLs in the public sitemap tree', function (): void {
        $language = Language::factory()->state(['locale' => 'en'])->create();
        $siteDomain = SiteDomain::factory()->recycle($language)->state(['scheme' => 'https', 'domain' => 'example.com', 'path' => 'test'])->create();

        Route::get('/admin/pages/{record}/edit', fn (string $record): string => $record)
            ->name('filament.admin.resources.pages.edit');

        $page = Page::factory()
            ->for($siteDomain->site)
            ->withTranslations()
            ->state([
                'name' => 'Test',
                'created_at' => Date::now(),
                'updated_at' => Date::now(),
            ])
            ->create();

        Cache::flush();

        $publicResult = (new SitemapBuilder(
            site: $siteDomain->site,
            domain: $siteDomain,
            language: $language,
        ))->build();

        expect($publicResult)->toHaveCount(1)
            ->and($publicResult->first())->toBeInstanceOf(SitemapPageData::class)
            ->and($publicResult->first()->pageId)->toBe($page->id)
            ->and($publicResult->first()->editUrl)->toBeNull();
    });

    it('merges duplicate sitemap nodes contributed by multiple page sources', function (): void {
        $language = Language::factory()->state(['locale' => 'en'])->create();
        $siteDomain = SiteDomain::factory()
            ->language($language)
            ->state(['scheme' => 'https', 'domain' => 'example.com', 'path' => null])
            ->create();

        $builder = new SitemapBuilder(site: $siteDomain->site, domain: $siteDomain, language: $language);

        $builder->addPages(new class implements Sitemapable
        {
            public function fetch(): Collection
            {
                return collect([
                    new SitemapPageData(
                        label: 'Services',
                        url: 'https://example.com/services',
                        children: collect([
                            new SitemapPageData(
                                label: 'Consulting',
                                url: 'https://example.com/services/consulting',
                                pageableType: 'page',
                                pageId: 20,
                            ),
                        ]),
                        changeFrequency: 'weekly',
                        priority: 0.5,
                        pageableType: 'page',
                        pageId: 10,
                    ),
                ]);
            }
        });

        $builder->addPages(new class implements Sitemapable
        {
            public function fetch(): Collection
            {
                return collect([
                    new SitemapPageData(
                        label: 'Services Updated',
                        url: 'https://example.com/services',
                        children: collect([
                            new SitemapPageData(
                                label: 'Consulting Updated',
                                url: 'https://example.com/services/consulting',
                                changeFrequency: 'daily',
                                pageableType: 'page',
                                pageId: 20,
                            ),
                            new SitemapPageData(
                                label: 'Support',
                                url: 'https://example.com/services/support',
                                pageableType: 'page',
                                pageId: 30,
                            ),
                        ]),
                        changeFrequency: 'monthly',
                        priority: 0.9,
                        pageableType: 'page',
                        pageId: 10,
                    ),
                ]);
            }
        });

        $result = $builder->build();
        $services = $result->first(fn (SitemapPageData $page): bool => $page->pageId === 10);

        expect($result)->toHaveCount(1)
            ->and($services)->toBeInstanceOf(SitemapPageData::class)
            ->and($services->label)->toBe('Services Updated')
            ->and($services->changeFrequency)->toBe('monthly')
            ->and($services->priority)->toBe(0.9)
            ->and($services->children)->toHaveCount(2)
            ->and($services->children->pluck('pageId')->all())->toBe([20, 30])
            ->and($services->children->first()->label)->toBe('Consulting Updated')
            ->and($services->children->first()->changeFrequency)->toBe('daily');
    });
});
