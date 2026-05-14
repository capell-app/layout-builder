<?php

declare(strict_types=1);

namespace Capell\DemoKit\Console\Commands;

use Capell\Core\Actions\CreateSiteAction;
use Capell\Core\Console\Commands\Concerns\PromptsWithOptionFallback;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Support\Creator\PageCreator;
use Capell\DemoKit\Actions\BuildDemoGenerationPlanAction;
use Capell\DemoKit\Actions\CreateDemoLanguagesAction;
use Capell\DemoKit\Actions\CreateDemoUsersAction;
use Capell\DemoKit\Console\Commands\Concerns\HasLanguagesOption;
use Capell\DemoKit\Console\Commands\Concerns\HasSitesOption;
use Capell\DemoKit\Data\DemoGenerationPlanData;
use Capell\DemoKit\Data\DemoPagePlanData;
use Capell\DemoKit\Data\DemoSiteGenerationPlanData;
use Capell\DemoKit\LayoutBuilder\Actions\CreateLayoutBuilderDemoSiteAction;
use Capell\DemoKit\LayoutBuilder\Data\DemoSitePlanData;
use Capell\DemoKit\Support\Creator\DemoCreator;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;
use InvalidArgumentException;

use function Laravel\Prompts\text;

use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;

class AdminDemoCommand extends Command
{
    use HasLanguagesOption;
    use HasSitesOption;
    use PromptsWithOptionFallback;

    private const PROGRESS_MESSAGE_WIDTH = 32;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inserts example demo sites and pages';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capell:admin-demo
        {--user=}
        {--languages=}
        {--url=}
        {--sites=}
        {--site-count=}
        {--page-count=}
        {--seed=}';

    private DemoCreator $demoCreator;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! CapellCore::isPackageInstalled('capell-app/admin')) {
            $this->warn('Capell Admin is not installed, skipping admin example site content.');

            return Command::SUCCESS;
        }

        try {
            $plan = BuildDemoGenerationPlanAction::run([
                'sites' => $this->resolveSites(),
                'site_count' => $this->resolvePositiveIntegerOption('site-count'),
                'pages' => $this->resolvePositiveIntegerOption('page-count'),
                'languages' => $this->resolveLanguages(),
                'seed' => $this->resolveSeedOption(),
            ]);
            $siteUrl = $this->resolveSiteUrl();
            $user = $this->resolveUser();

            $this->outputDemoSetupInfo($plan);
            $this->createDemoUsers();
            $this->demoCreator = app()->make(DemoCreator::class, [
                'url' => $siteUrl,
                'author' => $user,
            ]);

            $pageCreator = resolve(PageCreator::class);

            $this->line('Adding demo languages');
            CreateDemoLanguagesAction::run($plan->languageCodes);

            $this->createDemoSites($plan, $siteUrl, $pageCreator);

            $this->line('Setting up related sites');
            $this->demoCreator->setupRelatedSites();
        } catch (Throwable $throwable) {
            $this->error('Demo command failed: ' . $throwable->getMessage());
            throw_if(app()->environment('testing'), $throwable);

            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('Demo data inserted successfully');

        return Command::SUCCESS;
    }

    private function resolveSites(): array
    {
        if ($this->option('sites') !== null) {
            $sites = is_string($this->option('sites'))
                ? explode(',', $this->option('sites'))
                : (is_array($this->option('sites')) ? $this->option('sites') : []);

            return array_values(array_unique(array_filter(
                array_map(static fn (mixed $site): string => trim((string) $site), $sites),
                static fn (string $site): bool => $site !== '',
            )));
        }

        return $this->getDemoSites();
    }

    private function resolveLanguages(): array
    {
        if ($this->hasOption('languages') && $this->option('languages') !== null) {
            $languages = is_string($this->option('languages'))
                ? explode(',', $this->option('languages'))
                : (is_array($this->option('languages')) ? $this->option('languages') : []);

            return array_values(array_filter(
                array_map(static fn (mixed $language): string => trim((string) $language), $languages),
                static fn (string $language): bool => $language !== '',
            ));
        }

        return $this->getDemoLanguages();
    }

    private function resolveSiteUrl(): string
    {
        $siteUrl = $this->option('url');
        if ($siteUrl === null || $siteUrl === '') {
            $this->requireInteractiveOrFail('Site URL', 'Pass --url=<url>.');

            return text(
                label: 'Enter a base URL for the demo sites',
                default: SiteDomain::query()->first()->full_url ?? config('app.url'),
                required: true,
                validate: ['siteUrl' => 'url'],
            );
        }

        return $siteUrl;
    }

    private function resolveUser(): ?User
    {
        $user = $this->option('user');
        if ($user !== null) {
            $userModel = config('auth.providers.users.model');
            $user = str_contains((string) $user, '@')
                ? $userModel::query()->where('email', $user)->first()
                : $userModel::query()->whereKey($user)->first();
        }

        if (! $user && auth()->check()) {
            $maybeUser = auth()->user();

            return $maybeUser instanceof User ? $maybeUser : null;
        }

        return $user instanceof User ? $user : null;
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

    private function outputDemoSetupInfo(DemoGenerationPlanData $plan): void
    {
        $this->newLine();
        $this->comment('Inserting generated demo data for sites: ' . implode(', ', array_map(
            static fn (DemoSiteGenerationPlanData $site): string => $site->name,
            $plan->sites,
        )));

        if ($plan->seed !== null) {
            $this->comment('Demo seed: ' . $plan->seed);
        }

        $this->newLine();
    }

    private function createDemoUsers(): void
    {
        $this->line('Creating demo users');
        CreateDemoUsersAction::run();
        $this->info('Demo admin created with super admin role: demo@example.com');
        $this->info('Editor user created with editor role');
    }

    private function createDemoSites(DemoGenerationPlanData $plan, string $siteUrl, PageCreator $pageCreator): void
    {
        $sitesCount = count($plan->sites);

        $siteNumber = 0;

        foreach ($plan->sites as $siteIndex => $sitePlan) {
            $siteNumber++;

            /** @var Collection<int, Language> $siteLanguages */
            $siteLanguages = Language::query()
                ->whereIn('code', $sitePlan->languageCodes)
                ->get();
            $defaultLanguage = $siteLanguages->first();

            if (! $defaultLanguage instanceof Language) {
                continue;
            }

            $name = Str::title($sitePlan->name);
            $this->newLine();
            $this->info(sprintf('%d/%d. Site %s...', $siteNumber, $sitesCount, $name));

            $site = CreateSiteAction::run(
                $sitePlan->name,
                url: rtrim($siteUrl, '/') . ($siteIndex > 0 ? '/' . str()->slug($sitePlan->name) : ''),
                language: $defaultLanguage,
                languages: $siteLanguages,
            );

            $bar = $this->output->createProgressBar($sitePlan->pageCount() + 4);

            /** @var ProgressBar $bar */
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
            $bar->setMessage($this->formatProgressMessage('Home page'));

            $this->runProgressStep($bar, 'Home page', fn (): Page => $pageCreator->createHomePage($site, $siteLanguages));
            $this->runProgressStep($bar, 'Error page', fn (): Page => $pageCreator->createErrorPage($site, $siteLanguages));
            $this->runProgressStep($bar, sprintf(
                'Site setup: %s (%s)',
                $defaultLanguage->code,
                $siteLanguages->pluck('code')->implode(', '),
            ), fn () => $this->setupSite(
                demoData: $sitePlan,
                site: $site,
                languages: $siteLanguages,
                defaultLanguage: $defaultLanguage,
                bar: $bar,
            ));
            $this->runProgressStep($bar, 'Layout builder demo', fn () => CreateLayoutBuilderDemoSiteAction::run(
                new DemoSitePlanData(
                    site: $site,
                    contentTree: $sitePlan->toContentTree(),
                ),
            ));

            $bar->finish();
            $this->newLine();
        }
    }

    private function runProgressStep(ProgressBar $bar, string $message, callable $callback): void
    {
        $bar->setMessage($this->formatProgressMessage($message));

        $callback();

        $bar->advance();
    }

    private function setupSite(
        DemoSiteGenerationPlanData $demoData,
        Site $site,
        Collection $languages,
        Language $defaultLanguage,
        ProgressBar $bar,
    ): void {
        $this->demoCreator->setupSite($site, $languages);

        foreach ($demoData->pages as $pageData) {
            $this->createPagesWithProgress($pageData, $site, $languages, $defaultLanguage, bar: $bar);
        }
    }

    private function createPagesWithProgress(
        DemoPagePlanData $pageData,
        Site $site,
        Collection $languages,
        Language $defaultLanguage,
        Pageable|bool|null $parent = null,
        ?string $parentName = '',
        ?ProgressBar $bar = null,
    ): void {
        $pageNameSource = $pageData->name[$defaultLanguage->code] ?? $pageData->name['en'] ?? '';
        $pageName = Str::title($pageNameSource);
        $fullName = in_array($parentName, [null, '', '0'], true) ? $pageName : sprintf('%s » %s', $parentName, $pageName);

        if ($bar instanceof ProgressBar) {
            $bar->setMessage($this->formatProgressMessage($fullName));
        }

        $parent = $this->demoCreator->createPage(
            $pageData->toContentTreeNode(),
            $site,
            $languages,
            $parent,
            createMedia: $pageData->mediaCount > 0,
        );

        if ($bar instanceof ProgressBar) {
            $bar->advance();
        }

        foreach ($pageData->children as $childData) {
            $this->createPagesWithProgress($childData, $site, $languages, $defaultLanguage, $parent, $fullName, $bar);
        }
    }

    private function formatProgressMessage(string $message): string
    {
        if (Str::length($message) <= self::PROGRESS_MESSAGE_WIDTH) {
            return $message;
        }

        return Str::substr($message, 0, self::PROGRESS_MESSAGE_WIDTH - 3) . '...';
    }
}
