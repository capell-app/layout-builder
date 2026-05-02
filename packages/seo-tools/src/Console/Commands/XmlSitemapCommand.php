<?php

declare(strict_types=1);

namespace Capell\SeoTools\Console\Commands;

use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\SeoTools\Support\Sitemap\XmlSitemapGenerator;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class XmlSitemapCommand extends Command
{
    protected $description = 'Generate the sitemap XML files';

    protected $signature = 'capell:xml-sitemap
                            {--site= : Only regenerate sitemaps for this site ID}
                            {--incremental : Skip domains whose pages have not changed since the last run}';

    public function handle(): int
    {
        $sites = $this->getSites();
        $incremental = $this->option('incremental') !== null && $this->option('incremental') !== false;
        $rows = [];

        $sites->each(function (Site $site) use ($incremental, &$rows): void {
            $generator = resolve(XmlSitemapGenerator::class);

            if ($incremental) {
                $this->runIncremental($generator, $site, $rows);
            } else {
                $this->runFull($generator, $site, $rows);
            }
        });

        $headers = $incremental
            ? ['Domain', 'Language', 'URLs', 'File', 'Status']
            : ['Domain', 'Language', 'URLs', 'File'];

        $this->table($headers, $rows);

        $count = count($rows);
        $this->info(sprintf('%d %s generated successfully', $count, str('sitemap')->plural($count)));

        return Command::SUCCESS;
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function runFull(XmlSitemapGenerator $generator, Site $site, array &$rows): void
    {
        $generator->delete($site);

        $currentDomain = null;

        $generator->process(
            site: $site,
            start: function (SiteDomain $domain) use (&$currentDomain): void {
                $currentDomain = $domain;
            },
            end: function (int $total, string $filePath) use (&$currentDomain, &$rows): void {
                throw_unless($currentDomain instanceof SiteDomain, RuntimeException::class, 'Missing domain context in sitemap processing.');
                $rows[] = [
                    $currentDomain->domain,
                    $currentDomain->language->name,
                    $total,
                    $filePath,
                ];
            },
        );
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function runIncremental(XmlSitemapGenerator $generator, Site $site, array &$rows): void
    {
        $currentDomain = null;

        $generator->processIncremental(
            site: $site,
            start: function (SiteDomain $domain) use (&$currentDomain): void {
                $currentDomain = $domain;
            },
            end: function (int $total, string $filePath, bool $regenerated) use (&$currentDomain, &$rows): void {
                throw_unless($currentDomain instanceof SiteDomain, RuntimeException::class, 'Missing domain context in sitemap processing.');
                $rows[] = [
                    $currentDomain->domain,
                    $currentDomain->language->name,
                    $total,
                    $regenerated ? $filePath : '—',
                    $regenerated ? '<fg=green>regenerated</>' : '<fg=yellow>skipped</>',
                ];
            },
        );
    }

    private function getSites(): Collection
    {
        /** @var class-string<Site> $model */
        $model = Site::class;

        $query = $model::query()
            ->with('siteDomains.language')
            ->enabled();

        if ($this->option('site') !== null) {
            $query->where('id', $this->option('site'));
        }

        return $query->get();
    }
}
