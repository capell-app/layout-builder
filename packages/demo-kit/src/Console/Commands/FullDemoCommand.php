<?php

declare(strict_types=1);

namespace Capell\DemoKit\Console\Commands;

use Capell\Core\Data\PackageData;
use Capell\Core\Facades\CapellCore;
use Capell\DemoKit\Actions\BuildDemoGenerationPlanAction;
use Capell\DemoKit\Data\DemoSiteGenerationPlanData;
use Capell\DemoKit\Providers\DemoKitServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class FullDemoCommand extends Command
{
    protected $signature = 'capell:demo-kit-full-demo
        {--url=}
        {--user=}
        {--languages=}
        {--sites=}
        {--site-count=}
        {--page-count=}
        {--seed=}
        {--force}';

    protected $description = 'Create full multi-site and multi-language example data.';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->input->isInteractive()) {
            $this->error('Creating full example site data requires --force in non-interactive mode.');

            return Command::FAILURE;
        }

        $url = $this->resolveUrl();
        $plan = BuildDemoGenerationPlanAction::run([
            'sites' => $this->parseCsvOption('sites'),
            'site_count' => $this->resolvePositiveIntegerOption('site-count'),
            'pages' => $this->resolvePositiveIntegerOption('page-count'),
            'languages' => $this->resolveLanguages(),
            'seed' => $this->resolveSeedOption(),
        ]);
        $languages = $plan->languageCodes;
        $sites = array_map(
            static fn (DemoSiteGenerationPlanData $site): string => $site->name,
            $plan->sites,
        );

        $this->info('Creating full example sites and languages.');

        $adminDemoParams = [
            '--url' => $url,
            '--languages' => implode(',', $languages),
            '--sites' => implode(',', $sites),
        ];

        $pageCount = $this->resolvePositiveIntegerOption('page-count');
        if ($pageCount !== null) {
            $adminDemoParams['--page-count'] = $pageCount;
        }

        if ($plan->seed !== null) {
            $adminDemoParams['--seed'] = $plan->seed;
        }

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

        return ['all'];
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

    private function resolvePositiveIntegerOption(string $option): ?int
    {
        $value = $this->option($option);

        if (! is_scalar($value) || in_array((string) $value, ['', '0'], true)) {
            return null;
        }

        if (! ctype_digit((string) $value)) {
            throw new InvalidArgumentException(sprintf('The --%s option must be a positive integer.', $option));
        }

        $maximum = $option === 'site-count'
            ? BuildDemoGenerationPlanAction::MAX_SITE_COUNT
            : BuildDemoGenerationPlanAction::MAX_PAGE_COUNT;

        return min((int) $value, $maximum);
    }

    private function resolveSeedOption(): ?int
    {
        $seed = $this->option('seed');

        return is_scalar($seed) && (string) $seed !== '' ? (int) $seed : null;
    }

    /**
     * @return list<string>
     */
    private function demoPackageNames(): array
    {
        /** @var Collection<string, PackageData> $packages */
        $packages = CapellCore::getInstalledPackages();

        return $packages
            ->reject(fn (PackageData $package): bool => $package->name === DemoKitServiceProvider::$packageName)
            ->reject(fn (PackageData $package): bool => in_array($package->getDemoCommand(), [null, '', '0'], true))
            ->keys()
            ->values()
            ->all();
    }
}
