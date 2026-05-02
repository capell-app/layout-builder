<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\SiteDomain;
use Capell\SeoTools\Support\Sitemap\SitemapBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;

beforeEach(function (): void {
    Date::setTestNow(Date::create(2024, 1, 1, 0, 0, 0));
    Cache::flush();
});

describe('SitemapBuilder', function (): void {
    it('builds the expected sitemap structure from real models (snapshot)', function (): void {
        $language = Language::factory()->state(['locale' => 'en'])->create();
        $siteDomain = SiteDomain::factory()
            ->language($language)
            ->state(['scheme' => 'https', 'domain' => 'example.com', 'path' => 'test'])
            ->create();
        Page::factory()
            ->site($siteDomain->site)
            ->withTranslations()
            ->state([
                'name' => 'Test',
                'created_at' => Date::now(),
                'updated_at' => Date::now(),
            ])
            ->create();

        Cache::flush();
        $builder = new SitemapBuilder(site: $siteDomain->site, domain: $siteDomain, language: $language);
        $result = $builder->build();
        expect($result->toArray())->toMatchSnapshot();
    });

    it('builds the expected sitemap structure with editUrl from real models (snapshot)', function (): void {
        $language = Language::factory()->state(['locale' => 'en'])->create();
        $siteDomain = SiteDomain::factory()->recycle($language)->state(['scheme' => 'https', 'domain' => 'example.com', 'path' => 'test'])->create();

        Page::factory()
            ->for($siteDomain->site)
            ->withTranslations()
            ->state([
                'name' => 'Test',
                'created_at' => Date::now(),
                'updated_at' => Date::now(),
            ])
            ->create();

        Cache::flush();
        $builder = new SitemapBuilder(site: $siteDomain->site, domain: $siteDomain, language: $language, withEditUrl: true);
        $result = $builder->build();
        expect($result)
            ->toHaveCount(1)
            ->toMatchSnapshot();
    });
});
