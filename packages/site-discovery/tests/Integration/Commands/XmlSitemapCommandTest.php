<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\SiteDiscovery\Support\Sitemap\XmlSitemapGenerator;
use Capell\SiteDiscovery\Tests\SiteDiscoveryTestCase;
use Illuminate\Console\Command;

use function Pest\Laravel\artisan;

uses(SiteDiscoveryTestCase::class);

/**
 * @return XmlSitemapGenerator&object{
 *     deletedSiteIds: list<int>,
 *     processedSiteIds: list<int>,
 *     incrementalSiteIds: list<int>,
 *     incrementalDomains: list<array{site_id: int, domain: ?string, regenerated: bool}>
 * }
 */
function seoSuiteCommandFakeXmlSitemapGenerator(): XmlSitemapGenerator
{
    return new class extends XmlSitemapGenerator
    {
        /** @var list<int> */
        public array $deletedSiteIds = [];

        /** @var list<int> */
        public array $processedSiteIds = [];

        /** @var list<int> */
        public array $incrementalSiteIds = [];

        /** @var list<array{site_id: int, domain: ?string, regenerated: bool}> */
        public array $incrementalDomains = [];

        public function delete(Site $site): void
        {
            $this->deletedSiteIds[] = $site->id;
        }

        public function process(
            Site $site,
            ?Closure $start = null,
            ?Closure $prepare = null,
            ?Closure $checkpoint = null,
            ?Closure $end = null,
        ): void {
            $this->processedSiteIds[] = $site->id;

            $site->siteDomains->each(function (SiteDomain $domain) use ($start, $end): void {
                $start?->__invoke($domain);
                $end?->__invoke(3, 'sitemaps/' . $domain->getDomainKey() . '.xml');
            });
        }

        public function processIncremental(
            Site $site,
            ?Closure $start = null,
            ?Closure $prepare = null,
            ?Closure $checkpoint = null,
            ?Closure $end = null,
        ): void {
            $this->incrementalSiteIds[] = $site->id;

            $site->siteDomains->values()->each(function (SiteDomain $domain, int $domainIndex) use ($site, $start, $end): void {
                $regenerated = $domainIndex === 0;

                $this->incrementalDomains[] = [
                    'site_id' => $site->id,
                    'domain' => $domain->domain,
                    'regenerated' => $regenerated,
                ];

                $start?->__invoke($domain);
                $end?->__invoke(3, 'sitemaps/' . $domain->getDomainKey() . '.xml', $regenerated);
            });
        }
    };
}

it('generates full XML sitemaps for the selected enabled site', function (): void {
    $selectedSite = Site::factory()->withTranslations()->create();
    $otherSite = Site::factory()->withTranslations()->create();
    $disabledSite = Site::factory()->state(['status' => false])->withTranslations()->create();

    $generator = seoSuiteCommandFakeXmlSitemapGenerator();
    app()->instance(XmlSitemapGenerator::class, $generator);

    artisan('capell:xml-sitemap', ['--site' => $selectedSite->id])
        ->expectsOutputToContain('1 sitemap generated successfully')
        ->assertExitCode(Command::SUCCESS);

    expect($generator->deletedSiteIds)->toBe([$selectedSite->id])
        ->and($generator->processedSiteIds)->toBe([$selectedSite->id])
        ->and($generator->processedSiteIds)->not->toContain($otherSite->id)
        ->and($generator->processedSiteIds)->not->toContain($disabledSite->id)
        ->and($generator->incrementalSiteIds)->toBe([]);
});

it('runs incremental sitemap generation without deleting existing files', function (): void {
    $languages = Language::factory()->count(2)->create();
    $site = Site::factory()->language($languages[0])->withTranslations($languages)->create();

    $generator = seoSuiteCommandFakeXmlSitemapGenerator();
    app()->instance(XmlSitemapGenerator::class, $generator);

    artisan('capell:xml-sitemap', [
        '--site' => $site->id,
        '--incremental' => true,
    ])
        ->expectsOutputToContain('2 sitemaps generated successfully')
        ->assertExitCode(Command::SUCCESS);

    expect($generator->deletedSiteIds)->toBe([])
        ->and($generator->processedSiteIds)->toBe([])
        ->and($generator->incrementalSiteIds)->toBe([$site->id])
        ->and($generator->incrementalDomains)->toHaveCount(2)
        ->and(collect($generator->incrementalDomains)->pluck('regenerated')->all())->toBe([true, false]);
});
