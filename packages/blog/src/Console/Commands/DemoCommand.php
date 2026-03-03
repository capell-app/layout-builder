<?php

declare(strict_types=1);

namespace Capell\Blog\Console\Commands;

use Capell\Blog\Actions\CreateBlogPagesAction;
use Capell\Blog\Enums\ModelEnum as BlogModelEnum;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Blog\Support\Loader\BlogLoader;
use Capell\Core\Console\Commands\Concerns\HasSitesOption;
use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Core\Support\Creator\DemoCreator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\ProgressBar;

class DemoCommand extends Command
{
    use HasSitesOption;

    protected $signature = 'capell:blog-demo {--sites=} {--user=} {--limit=}';

    protected $description = 'Setup demo blog pages, tags and sample articles for selected sites.';

    private BlogCreator $blogCreator;

    private DemoCreator $demoCreator;

    // Add progress bar support mirroring layout demo
    private ?ProgressBar $progress = null;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $siteNames = $this->parseSitesOption();

        if ($siteNames === []) {
            $this->error('No sites selected or provided.');

            return self::FAILURE;
        }

        $sites = $this->resolveSites($siteNames);

        if ($sites->isEmpty()) {
            $this->error('Unable to find any sites for: ' . implode(', ', $siteNames));

            return self::FAILURE;
        }

        $user = $this->resolveUser();
        $limit = $this->parseLimitOption();

        foreach ($sites as $index => $site) {
            if ($index > 0) {
                $this->newLine();
            }

            $this->runDemoForSite($site, $user, $limit);
        }

        $this->info('Blog demo setup completed for selected sites.');

        return self::SUCCESS;
    }

    /**
     * Parse the --sites option into an array of site names.
     *
     * @return array<int, string>
     */
    private function parseSitesOption(): array
    {
        $sitesOption = $this->option('sites');

        if ($sitesOption) {
            if (is_array($sitesOption)) {
                return array_map(trim(...), $sitesOption);
            }

            // Treat as a single site name, even if it contains commas
            if (is_string($sitesOption)) {
                return [trim($sitesOption)];
            }
        }

        return $this->getDemoSites() ?? [];
    }

    /**
     * Resolve Site models for the given names.
     *
     * @param  array<int, string>  $siteNames
     * @return \Illuminate\Support\Collection<int, Site>
     */
    private function resolveSites(array $siteNames)
    {
        /** @var class-string<Site> $model */
        $model = CapellCore::getModel(\Capell\Core\Enums\ModelEnum::Site);

        return $model::query()
            ->with(['languages'])
            ->whereIn('name', $siteNames)
            ->get();
    }

    /**
     * Resolve the user for demo page authorship.
     */
    private function resolveUser(): ?Model
    {
        $userOption = $this->option('user');

        if ($userOption) {
            /** @var class-string<User> $model */
            $model = CapellCore::getModel('User');

            return $model::query()->find($userOption);
        }

        if (function_exists('auth') && auth()->check()) {
            $user = auth()->user();

            return $user instanceof Model ? $user : null;
        }

        return null;
    }

    /**
     * Parse and validate the --limit option.
     */
    private function parseLimitOption(): ?int
    {
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        if ($limit !== null && (! is_int($limit) || $limit < 1)) {
            $this->warn('The --limit option must be a positive integer. No demo pages will be created.');

            return null;
        }

        return $limit;
    }

    /**
     * Run the demo setup for a single site.
     */
    private function runDemoForSite(
        Site $site,
        ?Model $user,
        ?int $limit,
    ): void {
        $this->info('Setting up demo blog for site: ' . $site->name);
        $this->newLine();

        $this->demoCreator = new DemoCreator(author: $user);
        $this->blogCreator = resolve(BlogCreator::class);

        // Prepare total progress steps: pages to create + pages to tag + pages to associate
        $pagesTree = config('capell-demo.pages', []);
        $totalPagesAvailable = 0;
        foreach ($pagesTree as $node) {
            $totalPagesAvailable += $this->countContentNodes($node);
        }

        $pagesToCreate = $limit !== null ? min($totalPagesAvailable, $limit) : $totalPagesAvailable;

        /** @var class-string<Page> $pageModel */
        $pageModel = CapellCore::getModel(CoreModelEnum::Page);
        $pagesForTagsCount = $pageModel::query()
            ->where('site_id', $site->id)
            ->whereHas('children')
            ->whereRelation('type', 'key', 'article')
            ->limit(50)
            ->count();

        $totalSteps = $pagesToCreate + $pagesForTagsCount + 1; // +1 for CreateBlogPagesAction
        $this->startProgress($totalSteps);

        $this->setProgressMessage('Ensuring required blog and ancillary pages exist');
        CreateBlogPagesAction::run($site);
        $this->advanceProgress();

        $this->setProgressMessage('Creating demo pages');
        $created = $this->createDemoArticlesWithLimit($site, $user, $limit);

        if ($created) {
            $this->setProgressMessage('Demo pages created');
        } else {
            $this->setProgressMessage('Demo pages not created');
        }

        // Tag creation
        $this->setProgressMessage('Creating tags for site pages');
        $this->createTags($site, $site->languages);
        $this->setProgressMessage('Tags created/updated');

        // Tag association
        $this->setProgressMessage('Associating tags with pages');
        $this->associatePageTags($site);
        $this->setProgressMessage('Tags associated with pages');

        $this->finishProgress();
        $this->newLine();
    }

    /**
     * Create demo pages for a site, respecting the global limit.
     */
    private function createDemoArticlesWithLimit(
        Site $site,
        ?Model $user,
        ?int $limit = null,
    ): bool {
        $site->loadMissing('languages', 'language');
        $blogPage = BlogLoader::getBlogPage($site);

        if (! $blogPage instanceof Page) {
            $this->warn('Blog page not found for site: ' . $site->name);

            return false;
        }

        $demo = $this->getDemoData($site->name, $site->languages->pluck('code')->toArray());
        $createdCount = 0;

        $type = $this->blogCreator->createArticlePageType();

        foreach ($demo['children'] as $child) {
            if ($limit !== null && $createdCount >= $limit) {
                break;
            }

            $createdCount += $this->createDemoArticleRecursive(
                $child,
                $site,
                $site->languages,
                $site->language,
                $blogPage,
                '',
                $type,
                $user,
                $limit,
                $createdCount,
            );
        }

        return true;
    }

    private function getDemoData(?string $name, array $languages): array
    {
        $data = collect(config('capell-demo.pages'));

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

    /**
     * Recursively create demo pages, counting toward the global limit.
     * Returns the number of pages created in this branch.
     */
    private function createDemoArticleRecursive(
        array $data,
        Site $site,
        Collection $languages,
        Language $defaultLanguage,
        Page|bool $parent,
        string $parentName,
        Type $type,
        ?Model $author,
        ?int $limit,
        int $createdSoFar,
    ): int {
        if ($limit !== null && $createdSoFar >= $limit) {
            return 0;
        }

        $name = Str::title($data['name']['en']);

        $full_name = in_array($parentName, [null, '', '0'], true)
            ? $name
            : sprintf('%s » %s', $parentName, $name);

        $this->setProgressMessage('Creating page: ' . $full_name);

        $variations = [
            'The Ultimate Guide to',
            'A Guide to Caring for',
            'Discovering the Secrets of',
            'Exploring the',
            'The Complete Guide to',
        ];

        $title = Arr::random($variations);

        foreach ($languages as $language) {
            $data['title'][$language->code] = $title . ' ' . $data['name'][$language->code];
        }

        $page = $this->demoCreator->createPage($data, $site, $languages, $parent, $type);

        $this->advanceProgress();

        $created = 1;

        if (! isset($data['children']) || ($limit !== null && $createdSoFar + $created >= $limit)) {
            return $created;
        }

        foreach ($data['children'] as $child) {
            if ($limit !== null && $createdSoFar + $created >= $limit) {
                break;
            }

            $created += $this->createDemoArticleRecursive(
                $child,
                $site,
                $languages,
                $defaultLanguage,
                $parent === false ? false : $page,
                $full_name,
                $type,
                $author,
                $limit,
                $createdSoFar + $created,
            );
        }

        return $created;
    }

    private function createTags(Site $site, Collection $languages): void
    {
        /** @var class-string<Page> $model */
        $model = CapellCore::getModel(CoreModelEnum::Page);

        $pages = $model::query()
            ->where('site_id', $site->id)
            ->whereHas('children')
            ->whereRelation('type', 'key', 'article')
            ->with(['translations'])
            ->limit(50)
            ->get();

        $tagModel = CapellCore::getModel(BlogModelEnum::Tag);

        $pages->each(function (Page $page) use ($tagModel, $languages): void {
            $tag_names = [];
            $tag_slugs = [];
            $existingTag = null;
            $languages->each(function (Language $language) use (&$tag_names, &$tag_slugs, $page, $tagModel, &$existingTag): void {
                $translation = $page->translations->firstWhere('language_id', $language->id);
                if (! $translation) {
                    return;
                }

                $tag_names[$language->code] = Str::title($translation->label);
                $tag_slugs[$language->code] = Str::slug($translation->label);
                if ($existingTag === null) {
                    $existingTag = $tagModel::findFromString($translation->label, 'page', $language->code);
                }
            });
            if ($existingTag === null) {
                $tagModel::query()->create([
                    'type' => 'page',
                    'name' => $tag_names,
                    'slug' => $tag_slugs,
                ]);
            } else {
                $existingTag->update([
                    'name' => $tag_names,
                    'slug' => $tag_slugs,
                ]);
            }

            // Advance progress per processed page
            $this->advanceProgress();
        });
    }

    private function associatePageTags(Site $site): void
    {
        $tagModel = CapellCore::getModel(BlogModelEnum::Tag);
        $pageModel = CapellCore::getModel(CoreModelEnum::Page);
        $pages = $pageModel::query()
            ->with([
                'translations.language',
                'children',
            ])
            ->whereHas(
                'type',
                fn (BuilderContract $query): BuilderContract => $query->whereIn('key', ['default', 'article']),
            )
            ->notHomePage()
            ->where('site_id', $site->id)
            ->inRandomOrder()
            ->limit(50)
            ->get();
        foreach ($pages as $page) {
            $tag = false;
            foreach ($page->translations as $translation) {
                $tag = $tagModel::findFromString($translation->label, 'page', $translation->language->code);
                if ($tag) {
                    break;
                }
            }

            if ($tag) {
                $page->tags()->syncWithoutDetaching($tag);
                $page->children->take(10)->each(function (Page $childPage) use ($tag): void {
                    $childPage->tags()->syncWithoutDetaching($tag);
                });
            }

            // Advance progress per processed page
            $this->advanceProgress();
        }
    }

    // Progress bar helpers mirroring layout demo
    private function startProgress(int $max): void
    {
        $this->progress = $this->output->createProgressBar($max);
        $this->progress->setFormat(' [%bar%] %percent:3s%% | %message%');
        $this->progress->setMessage('');
        $this->progress->start();
    }

    private function setProgressMessage(string $message): void
    {
        if ($this->progress instanceof ProgressBar) {
            $this->progress->setMessage($message);
        }
    }

    private function advanceProgress(int $step = 1): void
    {
        if ($this->progress instanceof ProgressBar) {
            $this->progress->advance($step);
        }
    }

    private function finishProgress(): void
    {
        if ($this->progress instanceof ProgressBar) {
            $this->progress->finish();
            $this->newLine();
        }

        $this->progress = null;
    }

    // Count total demo content nodes (pages) recursively
    private function countContentNodes(array $data): int
    {
        $count = 1;
        if (isset($data['children']) && is_array($data['children'])) {
            foreach ($data['children'] as $child) {
                $count += $this->countContentNodes($child);
            }
        }

        return $count;
    }
}
