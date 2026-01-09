<?php

declare(strict_types=1);

namespace Capell\Blog\Console\Commands;

use Capell\Admin\Services\Creator\DemoCreator;
use Capell\Blog\Actions\CreateBlogPagesAction;
use Capell\Blog\Enums\ModelEnum as BlogModelEnum;
use Capell\Blog\Enums\ResourceEnum;
use Capell\Blog\Services\Loader\BlogLoader;
use Capell\Core\Console\Commands\Concerns\HasSitesOption;
use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class DemoCommand extends Command
{
    use HasSitesOption;

    /**
     * The name and signature of the console command.
     *
     * Sites can be provided as comma-separated list: --sites=site1,site2
     */
    protected $signature = 'capell-blog:demo {--sites=} {--user=} {--limit=}';

    /**
     * The console command description.
     */
    protected $description = 'Setup demo blog pages, tags and sample articles for selected sites.';

    private DemoCreator $demoCreator;

    public function handle(): int
    {
        $sitesOption = $this->option('sites');
        if ($sitesOption) {
            $siteOptions = is_string($sitesOption)
                ? explode(',', $sitesOption)
                : (is_array($sitesOption) ? $sitesOption : null);
        } else {
            $siteOptions = $this->getDemoSites();
        }

        if ($siteOptions === null || $siteOptions === []) {
            $this->error('No sites selected or provided.');

            return self::FAILURE;
        }

        $sites = CapellCore::getModel(CoreModelEnum::Site)::query()
            ->with(['languages'])
            ->whereIn('name', $siteOptions)
            ->get();

        if ($sites->isEmpty()) {
            $this->error('Unable to find any sites for: ' . implode(', ', $siteOptions));

            return self::FAILURE;
        }

        $userOption = $this->option('user');
        /** @var Model|null $user */
        $user = $userOption ? CapellCore::getModel('User')::query()->find($userOption) : null;

        if (! $user && function_exists('auth') && auth()->check()) {
            $user = auth()->user();
        }

        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        foreach ($sites as $index => $site) {
            if ($index > 0) {
                $this->newLine();
            }

            $this->info('Setting up demo blog for site: ' . $site->name);
            $this->newLine();
            $this->demoCreator = new DemoCreator(author: $user);

            $this->line('Ensuring required blog and ancillary pages exist...');
            CreateBlogPagesAction::run($site);
            $this->newLine();

            $this->line('Creating demo pages...');
            $created = $this->createDemoPages($site, $user, $limit);
            if ($created) {
                $this->info('Demo pages created.');
            } else {
                $this->warn('Demo pages not created.');
            }

            $this->newLine();

            $site->loadMissing('languages', 'language');
            $this->line('Creating tags for site pages...');
            $this->createTags($site, $site->languages);
            $this->info('Tags created/updated.');
            $this->newLine();

            $this->line('Associating tags with pages...');
            $this->associatePageTags($site);
            $this->info('Tags associated with pages.');
            $this->newLine();
        }

        $this->info('Blog demo setup completed for selected sites.');

        return self::SUCCESS;
    }

    private function createArticlePage(
        array $data,
        Site $site,
        Collection $languages,
        Language $defaultLanguage,
        null|bool|Page $parent = null,
        ?string $parentName = '',
        string $type = '',
        ?Model $author = null,
    ): void {
        $name = Str::title($data['name']['en']);
        if ($type !== '' && $type !== '0') {
            $name .= ' ' . Str::title($type);
        }

        $full_name = in_array($parentName, [null, '', '0'], true) ? $name : sprintf('%s » %s', $parentName, $name);

        $this->line('Creating page: ' . $full_name);

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
        $this->line(sprintf('Created page: %s (ID: ', $full_name) . ($page?->id ?? 'n/a') . ')');

        if (! isset($data['children'])) {
            return;
        }

        $this->line('Recursing into children of: ' . $full_name);

        foreach ($data['children'] as $child) {
            $this->createArticlePage(
                data: $child,
                site: $site,
                languages: $languages,
                defaultLanguage: $defaultLanguage,
                parent: $parent === false ? false : $page,
                parentName: $full_name,
                type: $type,
                author: $author,
            );
        }
    }

    private function createDemoPages(Site $site, ?Model $user, ?int $limit = null): bool
    {
        $site->loadMissing('languages', 'language');
        $blogPage = BlogLoader::getBlogPage($site);
        if (! $blogPage instanceof Page) {
            $this->warn('Blog page not found for site: ' . $site->name);

            return false;
        }

        $demo = config('capell-demo.pages');
        $demo = array_slice($demo, 0, $limit);

        $createdCount = 0;
        foreach ($demo as $pageData) {
            $this->line('Creating demo article: ' . ($pageData['name']['en'] ?? '[unnamed]'));
            $this->createArticlePage(
                $pageData,
                $site,
                $site->languages,
                $site->language,
                parent: $blogPage,
                type: strtolower(ResourceEnum::Article->name),
                author: $user,
            );
            $createdCount++;
        }

        $this->info(sprintf('%d demo articles created for site: %s', $createdCount, $site->name));

        return true;
    }

    private function createTags(Site $site, $languages): void
    {
        $pages = CapellCore::getModel(CoreModelEnum::Page)::query()
            ->where('site_id', $site->id)
            ->whereHas('children')
            ->whereRelation('type', 'key', 'article')
            ->with(['translations'])
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
                fn (BuilderContract $query) => $query->whereIn('key', ['default', 'article']),
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
                $page->children->each(fn (Page $childPage) => $childPage->tags()->syncWithoutDetaching($tag));
            }
        }
    }
}
