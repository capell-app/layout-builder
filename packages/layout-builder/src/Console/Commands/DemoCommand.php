<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Console\Commands;

use Capell\Core\Models\Site;
use Capell\DemoKit\Console\Commands\Concerns\HasSitesOption;
use Capell\LayoutBuilder\Actions\CreateLayoutBuilderDemoSiteAction;
use Capell\LayoutBuilder\Data\DemoSitePlanData;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class DemoCommand extends Command
{
    use HasSitesOption;

    protected $description = 'Inserts demo layout-builder layout widgets';

    protected $signature = 'capell:layout-builder-demo
         {--user= : Whether to associate the created demo content with the first user in the system. If not provided, content will be created without an associated user.}
         {--sites= : Comma-separated list of site names to target for demo content insertion. If not provided, all sites will be targeted.}
         {--skip-hero : Skip hero demo content after creating layout-builder demo content.}
     ';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $siteOptions = $this->getSiteOptions();

        /** @var class-string<Site> $model */
        $model = Site::class;

        $sites = $model::query()->with(['languages'])->whereIn('name', $siteOptions)->get();

        if ($sites->isEmpty()) {
            $this->error('Unable to find any sites for: ' . implode(', ', $siteOptions));

            return Command::FAILURE;
        }

        $contentTree = config('capell-demo-kit.pages')[0] ?? null;

        if (! is_array($contentTree)) {
            $this->error('Unable to find demo page content configuration.');

            return Command::FAILURE;
        }

        $user = $this->resolveUser();
        $createdAllSites = true;

        $sites->each(function (Site $site) use (&$createdAllSites, $contentTree, $user): void {
            $this->newLine();
            $this->comment('Creating demo content for site: ' . $site->name);

            $created = CreateLayoutBuilderDemoSiteAction::run(new DemoSitePlanData(
                site: $site,
                contentTree: $contentTree,
                user: $user,
            ));

            if (! $created) {
                $createdAllSites = false;
                $this->error('Unable to find homepage for site: ' . $site->name);
            }
        });

        if (! $createdAllSites) {
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('Demo layouts have been successfully created.');

        if (! $this->option('skip-hero')) {
            $this->newLine();
            $this->comment('Running hero demo...');
            $this->call('capell:hero-demo', [
                '--sites' => $this->option('sites'),
            ]);
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function getSiteOptions(): array
    {
        if ($this->option('sites')) {
            $sitesOption = $this->option('sites');

            if (is_string($sitesOption)) {
                return array_values(
                    array_filter(
                        array_map(trim(...), explode(',', $sitesOption)),
                        fn (string $siteOption): bool => $siteOption !== '',
                    ),
                );
            }

            if (is_array($sitesOption)) {
                return array_values(
                    array_filter(array_map(trim(...), $sitesOption), fn (string $siteOption): bool => $siteOption !== ''),
                );
            }

            return [];
        }

        return $this->getDemoSites();
    }

    private function resolveUser(): ?Model
    {
        if ($this->option('user')) {
            /** @var class-string<Model> $model */
            $model = config('auth.providers.users.model');
            $user = $model::query()->first();

            return $user instanceof Model ? $user : null;
        }

        $user = auth()->user();

        return $user instanceof Model ? $user : null;
    }
}
