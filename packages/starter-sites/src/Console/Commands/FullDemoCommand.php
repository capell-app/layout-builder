<?php

declare(strict_types=1);

namespace Capell\StarterSites\Console\Commands;

use Capell\Core\Data\PackageData;
use Capell\Core\Facades\CapellCore;
use Capell\StarterSites\Providers\StarterSitesServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

final class FullDemoCommand extends Command
{
    protected $signature = 'capell:starter-sites-full-demo {--url=} {--user=} {--languages=} {--sites=} {--force}';

    protected $description = 'Create full multi-site and multi-language example data.';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->input->isInteractive()) {
            $this->error('Creating full example site data requires --force in non-interactive mode.');

            return Command::FAILURE;
        }

        $url = $this->resolveUrl();
        $languages = $this->resolveLanguages();
        $sites = $this->resolveSites();

        $this->info('Creating full example sites and languages.');

        $adminDemoParams = [
            '--url' => $url,
            '--languages' => implode(',', $languages),
            '--sites' => implode(',', $sites),
        ];

        if ($this->option('user') !== null) {
            $adminDemoParams['--user'] = $this->option('user');
        }

        $adminDemoExitCode = $this->call('capell:admin-demo', $adminDemoParams);

        if ($adminDemoExitCode !== Command::SUCCESS) {
            return $adminDemoExitCode;
        }

        $packageNames = $this->demoPackageNames();

        if ($packageNames !== []) {
            $packageDemoExitCode = $this->call('capell:demo', [
                '--url' => $url,
                '--user' => $this->option('user') !== null,
                '--languages' => implode(',', $languages),
                '--sites' => implode(',', $sites),
                '--packages' => implode(',', $packageNames),
                '--force' => true,
            ]);

            if ($packageDemoExitCode !== Command::SUCCESS) {
                return $packageDemoExitCode;
            }
        }

        $this->info('Full example site data created successfully.');

        return Command::SUCCESS;
    }

    private function resolveUrl(): string
    {
        $url = $this->option('url');

        if (is_string($url) && $url !== '') {
            return $url;
        }

        return (string) config('app.url');
    }

    /**
     * @return list<string>
     */
    private function resolveLanguages(): array
    {
        $languages = $this->parseCsvOption('languages');

        if ($languages !== []) {
            return $languages;
        }

        return array_values(array_keys(config('capell-starter-sites.languages', [])));
    }

    /**
     * @return list<string>
     */
    private function resolveSites(): array
    {
        $sites = $this->parseCsvOption('sites');

        if ($sites !== []) {
            return $sites;
        }

        return collect([config('app.name')])
            ->merge(collect(config('capell-starter-sites.pages', []))
                ->map(fn (array $site): string => (string) ($site['name']['en'] ?? ''))
                ->filter(fn (string $site): bool => $site !== ''))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function parseCsvOption(string $option): array
    {
        $value = $this->option($option);

        if (is_array($value)) {
            return array_values(array_filter(
                array_map(static fn (mixed $item): string => trim((string) $item), $value),
                static fn (string $item): bool => $item !== '',
            ));
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        return array_values(array_filter(
            array_map(trim(...), explode(',', $value)),
            static fn (string $item): bool => $item !== '',
        ));
    }

    /**
     * @return list<string>
     */
    private function demoPackageNames(): array
    {
        /** @var Collection<string, PackageData> $packages */
        $packages = CapellCore::getInstalledPackages();

        return $packages
            ->reject(fn (PackageData $package): bool => $package->name === StarterSitesServiceProvider::$packageName)
            ->filter(fn (PackageData $package): bool => ! in_array($package->getDemoCommand(), [null, '', '0'], true))
            ->keys()
            ->values()
            ->all();
    }
}
