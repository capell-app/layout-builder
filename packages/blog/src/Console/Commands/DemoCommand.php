<?php

declare(strict_types=1);

namespace Capell\Blog\Console\Commands;

use Capell\Blog\Actions\CreateBlogPagesAction;
use Capell\Blog\Actions\EnsureArticlePublishingDefaultsAction;
use Capell\Blog\Enums\BlogLayoutEnum;
use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Blog\Models\Article;
use Capell\Blog\Support\Creator\ArticleCreator;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\TypeEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\StarterSites\Console\Commands\Concerns\HasSitesOption;
use Capell\StarterSites\Support\Creator\DemoCreator;
use Capell\Tags\Enums\TagTypeEnum;
use Capell\Tags\Models\Tag;
use Illuminate\Console\Command;
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
        $model = Site::class;

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
            $model = config('auth.providers.users.model');

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

        $this->demoCreator = resolve(DemoCreator::class, ['author' => $user]);

        // Calculate all steps upfront for an accurate progress bar
        $pagesTree = config('capell-starter-sites.pages', []);
        $totalPagesAvailable = 0;
        foreach ($pagesTree as $node) {
            $totalPagesAvailable += $this->countContentNodes($node);
        }

        $pagesToCreate = $limit !== null ? min($totalPagesAvailable, $limit) : $totalPagesAvailable;
        $existingArticleCount = $this->countExistingArticles($site);
        $taggingSteps = min($existingArticleCount + $pagesToCreate, 50);

        $totalSteps = 1 + $pagesToCreate + $taggingSteps; // 1 for CreateBlogPagesAction
        $this->startProgress($totalSteps);

        $this->setProgressMessage('Ensuring required blog and ancillary pages exist');
        CreateBlogPagesAction::run($site);
        $this->advanceProgress();

        $this->setProgressMessage('Creating demo pages');
        $created = $this->createArticles($site, $user, $limit);

        if ($created) {
            $this->setProgressMessage('Demo pages created');
        } else {
            $this->setProgressMessage('Demo pages not created');
        }

        // Tag creation
        $this->setProgressMessage('Creating tags for site pages');
        $this->createArticleTags($site, $site->languages);
        $this->setProgressMessage('Tags created/updated');

        $this->finishProgress();
        $this->newLine();
    }

    /**
     * Create demo pages for a site, respecting the global limit.
     */
    private function createArticles(
        Site $site,
        ?Model $user,
        ?int $limit = null,
    ): bool {
        $site->loadMissing('languages', 'language');

        $demo = $this->getDemoData($site->name, $site->languages->pluck('code')->toArray());
        $createdCount = 0;

        EnsureArticlePublishingDefaultsAction::run();

        $type = Type::query()
            ->where('key', BlogPageTypeEnum::Article->value)
            ->where('type', TypeEnum::Page->value)
            ->firstOrFail();

        $layout = Layout::query()
            ->where('key', BlogLayoutEnum::Article->value)
            ->firstOrFail();

        foreach ($demo['children'] as $child) {
            if ($limit !== null && $createdCount >= $limit) {
                break;
            }

            $createdCount += $this->createDemoArticleRecursive(
                $child,
                $site,
                $site->languages,
                $site->language,
                '',
                $type,
                $layout,
                $user,
                $limit,
                $createdCount,
            );
        }

        return true;
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

    /**
     * Recursively create demo pages, counting toward the global limit.
     * Returns the number of pages created in this branch.
     */
    private function createDemoArticleRecursive(
        array $data,
        Site $site,
        Collection $languages,
        Language $defaultLanguage,
        string $parentName,
        Type $type,
        Layout $layout,
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

        $pageCreator = resolve(ArticleCreator::class);

        $this->demoCreator->createPage($data, $site, $languages, type: $type, layout: $layout, pageCreator: $pageCreator);

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
                $full_name,
                $type,
                $layout,
                $author,
                $limit,
                $createdSoFar + $created,
            );
        }

        return $created;
    }

    private function createArticleTags(Site $site, Collection $languages): void
    {
        /** @var class-string<Page> $model */
        $pageModel = Page::class;

        /** @var class-string<Article> $model */
        $model = Article::class;

        $articles = $model::query()
            ->where('site_id', $site->id)
            ->whereRelation('type', 'key', BlogPageTypeEnum::Article->value)
            ->with(['translations'])
            ->limit(50)
            ->get();

        $articles->each(function (Article $article) use ($languages, $pageModel): void {
            $page = $pageModel::query()->firstWhere('name', $article->name);

            $page ??= $article;

            if ($page instanceof Article || $page->parent_id === null) {
                $tag = $this->createPageTag($page, $languages);
            } else {
                $tag = $this->getPageTag($page, $languages->first());

                if (! $tag instanceof Tag) {
                    $tag = $this->createPageTag($page, $languages);
                }
            }

            // Attach tag to page
            $article->tags()->syncWithoutDetaching($tag);

            // Advance progress per processed page
            $this->advanceProgress();
        });
    }

    private function createPageTag(Pageable $page, Collection $languages): Tag
    {
        $tagModel = Tag::class;

        $tag_names = [];
        $tag_slugs = [];
        $tag = null;

        $languages->each(function (Language $language) use (&$tag_names, &$tag_slugs, $page, $tagModel, &$tag): void {
            $translation = $page->translations->firstWhere('language_id', $language->id);
            if (! $translation) {
                return;
            }

            $tag_names[$language->code] = Str::title($translation->label);
            $tag_slugs[$language->code] = Str::slug($translation->label);

            if ($tag === null) {
                $tag = $tagModel::findFromString($translation->label, 'page', $language->code);
            }
        });

        if ($tag instanceof Tag) {
            $tag->update([
                'name' => $tag_names,
                'slug' => $tag_slugs,
            ]);

            return $tag;
        }

        return $tagModel::query()->create([
            'type' => TagTypeEnum::Page,
            'name' => $tag_names,
            'slug' => $tag_slugs,
        ]);
    }

    private function getPageTag(Pageable $page, Language $language): ?Tag
    {
        $root = method_exists($page, 'ancestors') ? $page->ancestors->first() : $page->parent;

        if ($root === null) {
            $root = $page;
        }

        $tagModel = Tag::class;

        $label = $root->translations->firstWhere('language_id', $language->id)->label;

        return $tagModel::findFromString($label, 'page', $language->code);
    }

    // Progress bar helpers mirroring layout demo
    private function startProgress(int $max): void
    {
        $this->progress = $this->output->createProgressBar($max);
        $this->progress->setFormat(' [%bar%] %percent:3s%% | %message%');
        $this->progress->setMessage('');
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

    private function countExistingArticles(Site $site): int
    {
        /** @var class-string<Article> $model */
        $model = Article::class;

        return min(
            $model::query()
                ->where('site_id', $site->id)
                ->whereRelation('type', 'key', BlogPageTypeEnum::Article->value)
                ->count(),
            50,
        );
    }

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
