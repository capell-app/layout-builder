<?php

declare(strict_types=1);

namespace Capell\StarterSites\Console\Commands;

use BezhanSalleh\FilamentShield\Support\Utils;
use Capell\Core\Actions\CreateSiteAction;
use Capell\Core\Console\Commands\Concerns\PromptsWithOptionFallback;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Support\Creator\PageCreator;
use Capell\StarterSites\Console\Commands\Concerns\HasLanguagesOption;
use Capell\StarterSites\Console\Commands\Concerns\HasSitesOption;
use Capell\StarterSites\Support\Creator\DemoCreator;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use function Laravel\Prompts\text;

use Spatie\Permission\Models\Role;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;

class AdminDemoCommand extends Command
{
    use HasLanguagesOption;
    use HasSitesOption;
    use PromptsWithOptionFallback;

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
    protected $signature = 'capell:admin-demo {--user=} {--languages=} {--url=} {--sites=}';

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
            $sites = $this->resolveSites();
            $languageOptions = $this->resolveLanguages();
            $siteUrl = $this->resolveSiteUrl();
            $user = $this->resolveUser();

            $this->outputDemoSetupInfo($sites);
            $this->createDemoUsers();
            $this->demoCreator = app()->make(DemoCreator::class, [
                'url' => $siteUrl,
                'author' => $user,
            ]);

            $pageCreator = resolve(PageCreator::class);

            $languages = $this->createAndFetchLanguages($languageOptions);

            $this->createDemoSites($sites, $languages, $siteUrl, $pageCreator);

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
            return is_string($this->option('sites'))
                ? explode(',', $this->option('sites'))
                : (is_array($this->option('sites')) ? $this->option('sites') : []);
        }

        return $this->getDemoSites();
    }

    private function resolveLanguages(): array
    {
        if ($this->hasOption('languages') && $this->option('languages') !== null) {
            return is_string($this->option('languages'))
                ? explode(',', $this->option('languages'))
                : (is_array($this->option('languages')) ? $this->option('languages') : []);
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
            $user = $userModel::find($user);
        }

        if (! $user && auth()->check()) {
            $maybeUser = auth()->user();

            return $maybeUser instanceof User ? $maybeUser : null;
        }

        return $user instanceof User ? $user : null;
    }

    private function outputDemoSetupInfo(array $sites): void
    {
        $this->newLine();
        $this->comment('Inserting demo data for selected sites: ' . implode(', ', $sites));
        $this->newLine();
    }

    private function createDemoUsers(): void
    {
        $this->line('Creating demo users');
        $this->createUser(
            name: 'Demo Admin',
            email: 'demo@example.com',
            password: 'password',
            roleName: Utils::getSuperAdminName(),
        );
        $this->info('Demo admin created with super admin role: demo@example.com');
        $this->createUser(
            name: 'Demo Editor',
            email: 'editor@example.com',
            password: 'password',
            roleName: 'editor',
        );
        $this->info('Editor user created with editor role');
    }

    private function createAndFetchLanguages(array $languageOptions): Collection
    {
        $this->line('Adding demo languages');
        $this->demoCreator->createDefaultLanguages($languageOptions);

        /** @var class-string<Language> $model */
        $model = Language::class;

        /** @var Collection<int, Language> $languages */
        $languages = $model::query()
            ->when(
                $languageOptions,
                fn (Builder $query): Builder => $query->whereIn('code', $languageOptions),
            )
            ->get();

        return $languages;
    }

    private function createDemoSites(array $sites, Collection $allLanguages, string $siteUrl, PageCreator $pageCreator): void
    {
        $languageCodes = $allLanguages->pluck('code')->toArray();

        $sites_count = count($sites);
        $site_no = 0;

        foreach ($sites as $i => $siteName) {
            $site_no++;

            $demoData = self::getDemoData($siteName, $languageCodes);

            if ($i === 0) {
                /** @var Collection<int, Language> $siteLanguages */
                $siteLanguages = $allLanguages;

                $defaultLanguage = $siteLanguages->first();
            } else {
                $subset = $allLanguages->random(random_int(1, $allLanguages->count()))->values();

                /** @var Collection<int, Language> $siteLanguages */
                $siteLanguages = new Collection($subset->all());

                $defaultLanguage = $siteLanguages->random();
            }

            $name = Str::title($demoData['name'][$defaultLanguage->code] ?? $demoData['name']['en']);
            $this->newLine();
            $this->info(sprintf('%d/%d. Site %s...', $site_no, $sites_count, $name));

            $site = CreateSiteAction::run(
                $siteName,
                url: rtrim($siteUrl, '/') . ($i > 0 ? '/' . str()->slug($siteName) : ''),
                language: $defaultLanguage,
                languages: $siteLanguages,
            );

            $this->line('Home page');
            $pageCreator->createHomePage($site, $siteLanguages);

            $this->line('Error page');
            $pageCreator->createErrorPage($site, $siteLanguages);

            $this->line('Setting up site');
            $this->setupSite(
                demoData: $demoData['children'],
                site: $site,
                languages: $siteLanguages,
                defaultLanguage: $defaultLanguage,
            );
        }
    }

    private function countPages(array $pages): int
    {
        $count = 0;
        foreach ($pages as $page) {
            $count++;
            if (isset($page['children']) && is_array($page['children'])) {
                $count += $this->countPages($page['children']);
            }
        }

        return $count;
    }

    private function setupSite(
        array $demoData,
        Site $site,
        Collection $languages,
        Language $defaultLanguage,
    ): void {
        $this->line('Setting up site with default language: ' . $defaultLanguage->code);
        $this->line('Setting up languages: ' . $languages->pluck('code')->implode(', '));
        $this->demoCreator->setupSite($site, $languages);

        $this->newLine();
        $this->line('Setting up pages');
        $totalPages = $this->countPages($demoData);
        $bar = $this->output->createProgressBar($totalPages);

        /** @var ProgressBar $bar */
        // Configure progress bar to display current page message
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('Starting...');
        $bar->start();

        foreach ($demoData as $pageData) {
            $this->createPagesWithProgress($pageData, $site, $languages, $defaultLanguage, bar: $bar);
        }

        $bar->finish();
        $this->newLine();
    }

    private function createPagesWithProgress(
        array $pageData,
        Site $site,
        Collection $languages,
        Language $defaultLanguage,
        Pageable|bool|null $parent = null,
        ?string $parentName = '',
        ?ProgressBar $bar = null,
    ): void {
        /** @var array $pageData */
        // Compute a user-friendly page label for the progress message
        $pageNameSource = $pageData['name'][$defaultLanguage->code] ?? $pageData['name']['en'] ?? '';
        $pageName = Str::title((string) $pageNameSource);
        $fullName = in_array($parentName, [null, '', '0'], true) ? $pageName : sprintf('%s » %s', $parentName, $pageName);

        if ($bar instanceof ProgressBar) {
            $bar->setMessage($fullName);
        }

        $parent = $this->demoCreator->createPage($pageData, $site, $languages, $parent);

        if ($bar instanceof ProgressBar) {
            $bar->advance();
        }

        if (isset($pageData['children']) && is_array($pageData['children'])) {
            foreach ($pageData['children'] as $childData) {
                $this->createPagesWithProgress($childData, $site, $languages, $defaultLanguage, $parent, $fullName, $bar);
            }
        }
    }

    private function createUser(string $name, string $email, string $password, string $roleName): void
    {
        /** @var class-string<User> $userModel */
        $userModel = config('auth.providers.users.model');

        $panelUserRole = Role::findOrCreate($roleName);

        /** @var User $user */
        $user = $userModel::query()->where('email', $email)->first() ?? new $userModel;
        $user->email = $email;
        $user->name = $name;
        $user->password = Hash::make($password);
        $user->save();

        $user->assignRole($panelUserRole);
    }

    private function getDemoData(?string $name, array $languages): array
    {
        $data = collect(config('capell-starter-sites.pages'));

        if ($name !== null && $data->where('name.en', $name)->isNotEmpty()) {
            $data = $data->firstWhere(fn (array $item): bool => $item['name']['en'] === $name);
        } else {
            $data = [
                'name' => array_combine($languages, array_fill(0, count($languages), $name)),
                'children' => $data->pluck('children')->flatten(1)->toArray(),
            ];
        }

        if ($languages !== []) {
            $filterLanguages = function (array $item) use (&$filterLanguages, $languages): array {
                if (isset($item['name']) && is_array($item['name'])) {
                    $item['name'] = array_intersect_key($item['name'], array_flip($languages));
                }

                if (isset($item['children']) && is_array($item['children'])) {
                    $item['children'] = array_map($filterLanguages, $item['children']);
                }

                return $item;
            };

            $data['children'] = array_map($filterLanguages, $data['children']);
        }

        return $data;
    }
}
