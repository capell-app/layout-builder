<?php

declare(strict_types=1);

namespace Capell\Navigation\Console\Commands;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Support\Creator\NavigationDemoCreator;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class DemoCommand extends Command
{
    protected $signature = 'capell:navigation-demo {--sites=} {--languages=}';

    protected $description = 'Set up navigation demo data for sites.';

    public function handle(): int
    {
        try {
            $navigationDemoCreator = resolve(NavigationDemoCreator::class);

            $sites = $this->resolveSites();
            $languageCodes = $this->resolveLanguageCodes();

            $sites->each(function (Site $site) use ($navigationDemoCreator, $languageCodes): void {
                $home = Page::getSiteHomePage($site);

                if (! $home instanceof Page) {
                    $this->warn(sprintf('Skipping site "%s": no home page found.', $site->name));

                    return;
                }

                $languages = $this->resolveSiteLanguages($site, $languageCodes);

                foreach ($languages as $language) {
                    $this->line(sprintf('Setting up navigation for "%s" (%s)', $site->name, $language->code));
                    $navigationDemoCreator->setupMainNavigation($site, $language, $home);
                    $navigationDemoCreator->setupFooterNavigation($site, $language);
                    $navigationDemoCreator->setupSubFooterNavigation($site, $language);
                }
            });

            $this->line('Updating related site navigations');
            $navigationDemoCreator->updateRelatedSiteNavigations();
        } catch (Throwable $throwable) {
            $this->error('Navigation demo command failed: ' . $throwable->getMessage());
            throw_if(app()->environment('testing'), $throwable);

            return Command::FAILURE;
        }

        $this->info('Navigation demo data set up successfully.');

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

    private function resolveLanguageCodes(): ?array
    {
        $languageOption = $this->option('languages');

        if (is_string($languageOption) && $languageOption !== '') {
            return explode(',', $languageOption);
        }

        if (is_array($languageOption)) {
            return $languageOption;
        }

        return null;
    }

    /** @return Collection<int, Language> */
    private function resolveSiteLanguages(Site $site, ?array $languageCodes): Collection
    {
        if ($languageCodes === null) {
            return $site->languages;
        }

        return $site->languages->filter(
            fn (Language $language): bool => in_array($language->code, $languageCodes, true),
        )->values();
    }
}
