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
use Capell\DemoKit\Console\Commands\Concerns\HasSitesOption;
use Capell\DemoKit\Support\Creator\DemoCreator;
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

    private ?ProgressBar $progress = null;

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
     * @return list<string>
     */
    private function parseSitesOption(): array
    {
        $sitesOption = $this->option('sites');

        if (is_string($sitesOption) && $sitesOption !== '') {
            return [trim($sitesOption)];
        }

        if (is_array($sitesOption)) {
            return array_values(array_filter(array_map(
                static fn (mixed $siteName): string => is_string($siteName) ? trim($siteName) : '',
                $sitesOption,
            ), static fn (string $siteName): bool => $siteName !== ''));
        }

        return $this->getDemoSites() ?? [];
    }

    /**
     * @param  list<string>  $siteNames
     * @return \Illuminate\Support\Collection<int, Site>
     */
    private function resolveSites(array $siteNames): \Illuminate\Support\Collection
    {
        /** @var class-string<Site> $siteModel */
        $siteModel = Site::class;

        return $siteModel::query()
            ->with(['languages'])
            ->whereIn('name', $siteNames)
            ->get();
    }

    private function resolveUser(): ?Model
    {
        $userOption = $this->option('user');

        if (! in_array($userOption, [null, false, ''], true)) {
            /** @var class-string<User> $userModel */
            $userModel = config('auth.providers.users.model');

            return $userModel::query()->find($userOption);
        }

        if (function_exists('auth') && auth()->check()) {
            $user = auth()->user();

            return $user instanceof Model ? $user : null;
        }

        return null;
    }

    private function parseLimitOption(): ?int
    {
        $limitOption = $this->option('limit');
        $limit = in_array($limitOption, [null, false, ''], true) ? null : (int) $limitOption;

        if ($limit !== null && $limit < 1) {
            $this->warn('The --limit option must be a positive integer. No demo pages will be created.');

            return null;
        }

        return $limit;
    }

    private function runDemoForSite(Site $site, ?Model $user, ?int $limit): void
    {
        $this->info('Setting up demo blog for site: ' . $site->name);
        $this->newLine();

        $this->demoCreator = resolve(DemoCreator::class, ['author' => $user]);

        $pagesTree = config('capell-demo-kit.pages', []);
        $totalPagesAvailable = 0;

        foreach ($pagesTree as $node) {
            $totalPagesAvailable += $this->countContentNodes($node);
        }

        $pagesToCreate = $limit !== null ? min($totalPagesAvailable, $limit) : $totalPagesAvailable;
        $existingArticleCount = $this->countExistingArticles($site);
        $taggingSteps = min($existingArticleCount + $pagesToCreate, 50);

        $this->startProgress(1 + $pagesToCreate + $taggingSteps);

        $this->setProgressMessage('Ensuring required blog and ancillary pages exist');
        CreateBlogPagesAction::run($site);
        $this->advanceProgress();

        $this->setProgressMessage('Creating demo pages');
        $created = $this->createArticles($site, $user, $limit);

        $this->setProgressMessage($created ? 'Demo pages created' : 'Demo pages not created');
        $this->setProgressMessage('Creating tags for site pages');
        $this->createArticleTags($site, $site->languages);
        $this->setProgressMessage('Tags created/updated');

        $this->finishProgress();
        $this->newLine();
    }

    private function createArticles(Site $site, ?Model $user, ?int $limit = null): bool
    {
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

    /**
     * @param  list<string>  $languages
     * @return array<string, mixed>
     */
    private function getDemoData(?string $name, array $languages): array
    {
        $data = collect(config('capell-demo-kit.pages'));

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
     * @param  array<string, mixed>  $data
     * @param  Collection<int, Language>  $languages
     */
    private function createDemoArticleRecursive(
        array $data,
        Site $site,
        Collection $languages,
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
        $fullName = $parentName === '' ? $name : sprintf('%s » %s', $parentName, $name);

        $this->setProgressMessage('Creating page: ' . $fullName);

        $title = Arr::random([
            'The Ultimate Guide to',
            'A Guide to Caring for',
            'Discovering the Secrets of',
            'Exploring the',
            'The Complete Guide to',
        ]);

        foreach ($languages as $language) {
            $languageCode = $language->getAttribute('code');

            if (! is_string($languageCode)) {
                continue;
            }

            $data['title'][$languageCode] = $title . ' ' . $data['name'][$languageCode];
        }

        $articleCreator = resolve(ArticleCreator::class);

        $this->demoCreator->createPage($data, $site, $languages, type: $type, layout: $layout, pageCreator: $articleCreator);
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
                $fullName,
                $type,
                $layout,
                $author,
                $limit,
                $createdSoFar + $created,
            );
        }

        return $created;
    }

    /**
     * @param  Collection<int, Language>  $languages
     */
    private function createArticleTags(Site $site, Collection $languages): void
    {
        /** @var class-string<Page> $pageModel */
        $pageModel = Page::class;

        /** @var class-string<Article> $articleModel */
        $articleModel = Article::class;

        $articles = $articleModel::query()
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

            $article->tags()->syncWithoutDetaching($tag);
            $this->advanceProgress();
        });
    }

    /**
     * @param  Collection<int, Language>  $languages
     */
    private function createPageTag(Pageable $page, Collection $languages): Tag
    {
        /** @var class-string<Tag> $tagModel */
        $tagModel = Tag::class;

        $tagNames = [];
        $tagSlugs = [];
        $tag = null;

        $languages->each(function (Language $language) use (&$tagNames, &$tagSlugs, $page, $tagModel, &$tag): void {
            $translation = $page->translations->firstWhere('language_id', $language->id);

            if ($translation === null) {
                return;
            }

            $tagNames[$language->code] = Str::title($translation->label);
            $tagSlugs[$language->code] = Str::slug($translation->label);

            if ($tag === null) {
                $tag = $tagModel::findFromString($translation->label, 'page', $language->code);
            }
        });

        if ($tag instanceof Tag) {
            $tag->update([
                'name' => $tagNames,
                'slug' => $tagSlugs,
            ]);

            return $tag;
        }

        return $tagModel::query()->create([
            'type' => TagTypeEnum::Page,
            'name' => $tagNames,
            'slug' => $tagSlugs,
        ]);
    }

    private function getPageTag(Pageable $page, Language $language): ?Tag
    {
        $root = method_exists($page, 'ancestors') ? $page->ancestors->first() : $page->parent;

        if ($root === null) {
            $root = $page;
        }

        /** @var class-string<Tag> $tagModel */
        $tagModel = Tag::class;

        $translation = $root->translations->firstWhere('language_id', $language->id);

        if ($translation === null) {
            return null;
        }

        return $tagModel::findFromString($translation->label, 'page', $language->code);
    }

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
        /** @var class-string<Article> $articleModel */
        $articleModel = Article::class;

        return min(
            $articleModel::query()
                ->where('site_id', $site->id)
                ->whereRelation('type', 'key', BlogPageTypeEnum::Article->value)
                ->count(),
            50,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
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
