<?php

declare(strict_types=1);

namespace Capell\Navigation\Console\Commands;

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Support\Creator\NavigationCreator;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class SetupCommand extends Command
{
    protected $signature = 'capell:navigation-setup {--sites=}';

    protected $description = 'Create initial navigation structure for sites.';

    public function handle(): int
    {
        try {
            $sites = $this->resolveSites();

            $sites->each(function (Site $site): void {
                $this->line(sprintf('Setting up navigation for "%s"', $site->name));

                $home = Page::getSiteHomePage($site);

                if (! $home instanceof Page) {
                    $this->warn(sprintf('Skipping site "%s": no home page found.', $site->name));

                    return;
                }

                $sitemapPage = $site->getFirstPageByType('sitemap');

                $navigationCreator = resolve(NavigationCreator::class);

                $navigationCreator->mainNavigation(site: $site, home: $home);
                $navigationCreator->footerNavigation(site: $site);
                $navigationCreator->subFooterNavigation(
                    site: $site,
                    pages: $sitemapPage instanceof Page ? new Collection([$sitemapPage]) : null,
                );
            });
        } catch (Throwable $throwable) {
            $this->error('Navigation setup command failed: ' . $throwable->getMessage());
            throw_if(app()->environment('testing'), $throwable);

            return Command::FAILURE;
        }

        $this->info('Navigation setup complete.');

        return Command::SUCCESS;
    }

    private function resolveSites(): Collection
    {
        $siteOptions = $this->option('sites');

        if (is_string($siteOptions) && $siteOptions !== '') {
            $siteOptions = explode(',', $siteOptions);
        } elseif (! is_array($siteOptions)) {
            $siteOptions = null;
        }

        /** @var Collection<int, Site> $sites */
        $sites = Site::query()
            ->with(['language', 'languages', 'translations'])
            ->when(
                is_array($siteOptions),
                fn (Builder $query): Builder => $query->whereIn('name', $siteOptions),
            )
            ->get();

        return $sites;
    }
}
