<?php

declare(strict_types=1);

namespace Capell\Blog\Actions;

use Capell\Admin\Services\Creator\DemoCreator;
use Capell\Blog\Enums\ModelEnum;
use Capell\Blog\Enums\ResourceEnum;
use Capell\Blog\Services\Loader\BlogLoader;
use Capell\Core\Commands\Concerns\HasSitesOption;
use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run(Site $site, ?Model $author = null, ?int $limit = null)
 */
class DemoAction
{
    use AsObject;
    use HasSitesOption;

    private DemoCreator $demoCreator;

    // Handle a single site's demo blog setup.
    public function handle(Site $site, ?Model $author = null, ?int $limit = null): void
    {
        // Default limit
        $limit ??= 20;

        // Author-aware creator
        $this->demoCreator = new DemoCreator(author: $author);

        // Ensure required blog page and ancillary pages exist.
        CreateBlogPagesAction::run($site);

        // Create site-wide tags structure.
        $site->loadMissing('languages', 'language');
        $this->createSiteTags($this->demoTags(), $site, $site->languages);

        // Tag existing pages opportunistically.
        $this->setupPageTags($site);

        // Create demo pages if under the limit.
        $this->createDemoPages($site, $author, $limit);
    }

    private function createPage(
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

        $full_name = in_array($parentName, [null, '', '0'], true) ? $name : sprintf('%s &raquo; %s', $parentName, $name);

        $page = $this->demoCreator->createPage($data, $site, $languages, $parent, $type);

        if (! isset($data['children'])) {
            return;
        }

        foreach ($data['children'] as $child) {
            $this->createPage(
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
            // Log via console if available (when running as command) but return false silently otherwise.
            return false;
        }

        $limit ??= 20;

        $totalBlogPages = Page::query()
            ->where('site_id', $site->id)
            ->whereRelation('type', 'key', strtolower(ResourceEnum::Article->name))
            ->count();

        if ($totalBlogPages >= $limit) {
            return true;
        }

        $demo = config('capell-demo.pages');
        $demo = array_slice($demo, 0, $limit);

        foreach ($demo as $pageData) {
            $this->createPage(
                $pageData,
                $site,
                $site->languages,
                $site->language,
                parent: $blogPage,
                type: strtolower(ResourceEnum::Article->name),
                author: $user,
            );
        }

        return true;
    }

    private function createSiteTags(array $data, Site $site, $languages): void
    {
        $tag_name = [];
        $tag_slug = [];
        foreach ($data['name'] as $lang_code => $name) {
            $language = $languages->firstWhere('code', $lang_code);
            if (! $language) {
                continue;
            }

            $tag_name[$language->code] = Str::title($name);
            $tag_slug[$language->code] = Str::slug($name);
        }

        $tagModel = CapellCore::getModel(ModelEnum::Tag);

        $tagModel::firstOrCreate([
            'type' => 'page',
            'name' => $tag_name,
            'slug' => $tag_slug,
            'site_id' => $site->id,
        ]);

        foreach ($data['children'] as $child) {
            if (! isset($child['children'])) {
                continue;
            }

            $this->createSiteTags($child, $site, $languages);
        }
    }

    private function setupPageTags(Site $site): void
    {
        $tagModel = CapellCore::getModel(ModelEnum::Tag);
        $pageModel = CapellCore::getModel(CoreModelEnum::Page);

        $pages = $pageModel::whereHas(
            'type',
            fn (BuilderContract $query) => $query->whereIn('key', ['default', 'article']),
        )
            ->with([
                'translations.language',
                'children',
            ])
            ->notHomePage()
            ->where('site_id', $site->id)
            ->inRandomOrder()
            ->limit(50)
            ->get();

        foreach ($pages as $page) {
            $tag = false;
            foreach ($page->translations as $translation) {
                $tag = $tagModel::findFromString($translation->title, 'page', $translation->language->code);
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

    private function demoTags(): array
    {
        return [
            'name' => [
                'en' => 'Technology',
                'es' => 'Tecnología',
                'fr' => 'Technologie',
                'de' => 'Technik',
            ],
            'children' => [
                [
                    'name' => [
                        'en' => 'Software',
                        'es' => 'Software',
                        'fr' => 'Logiciel',
                        'de' => 'Software',
                    ],
                    'children' => [],
                ],
                [
                    'name' => [
                        'en' => 'Hardware',
                        'es' => 'Hardware',
                        'fr' => 'Matériel',
                        'de' => 'Hardware',
                    ],
                    'children' => [],
                ],
                [
                    'name' => [
                        'en' => 'Gadgets',
                        'es' => 'Dispositivos',
                        'fr' => 'Gadgets',
                        'de' => 'Gadgets',
                    ],
                    'children' => [],
                ],
            ],
        ];
    }
}
