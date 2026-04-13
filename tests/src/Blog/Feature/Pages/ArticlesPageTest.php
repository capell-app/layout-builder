<?php

declare(strict_types=1);

use Capell\Blog\Actions\GenerateArchiveUrl;
use Capell\Blog\Data\ArchiveMonthData;
use Capell\Blog\Enums\TagTypeEnum;
use Capell\Blog\Models\Article;
use Capell\Blog\Models\Tag;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Frontend\Enums\CacheEnum;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Carbon\CarbonImmutable;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

beforeEach(function (): void {
    config(['capell-core.disable_cache_save_keys' => [CacheEnum::Pages->value . '-*']]);
});

test('blog page lists articles', function (): void {
    $blogCreator = resolve(BlogCreator::class);

    $siteDomain = SiteDomain::factory()->default()->create();
    $site = $siteDomain->site;

    $blogPage = $blogCreator->createBlogPage($site);
    $blogUrl = $blogPage->pageUrl;

    $articleType = $blogCreator->createArticlePageType();
    $articleLayout = $blogCreator->createArticleLayout();

    $archivesPage = $blogCreator->createArchivesPage($blogPage);
    $blogCreator->createArchivePage($archivesPage);

    $tagsPage = $blogCreator->createTagsPage($site, $blogPage);
    $blogCreator->createTagPage($site, $tagsPage);

    $articles = Article::factory()
        ->count(3)
        ->site($siteDomain->site)
        ->layout($articleLayout)
        ->type($articleType)
        ->withTranslations($site->languages)
        ->create();

    expect($blogPage)
        ->toBeInstanceOf(Page::class)
        ->type->name->toBe('Blog')
        ->layout->name->toBe('Blog Posts');

    get($blogUrl->full_url)
        ->assertOk()
        ->assertSeeHtml($blogPage->title)
        ->assertSeeHtml($articles[0]->title)
        ->assertSeeHtml($articles[1]->title)
        ->assertSeeHtml($articles[2]->title);
});

test('visit blogs page with no articles and see appropriate message', function (): void {
    $blogCreator = resolve(BlogCreator::class);

    $siteDomain = SiteDomain::factory()->default()->create();
    $site = $siteDomain->site;

    $blogPage = $blogCreator->createBlogPage($site);
    $blogUrl = $blogPage->pageUrl;

    expect($blogPage)
        ->toBeInstanceOf(Page::class)
        ->type->name->toBe('Blog')
        ->layout->name->toBe('Blog Posts');

    get($blogUrl->full_url)
        ->assertOk()
        ->assertSeeText(__('capell-blog::messages.no_articles_found'));
});

test('article page', function (): void {
    $blogCreator = resolve(BlogCreator::class);

    $siteDomain = SiteDomain::factory()->default()->create();
    $site = $siteDomain->site;

    $blogPage = $blogCreator->createBlogPage($site);
    $articleType = $blogCreator->createArticlePageType();
    $articleLayout = $blogCreator->createArticleLayout();

    $archivesPage = $blogCreator->createArchivesPage($blogPage);
    $blogCreator->createArchivePage($archivesPage);

    $tagsPage = $blogCreator->createTagsPage($site, $blogPage, createWidgets: true);
    $tagPage = $blogCreator->createTagPage($site, $tagsPage);

    $article = Article::factory()
        ->site($siteDomain->site)
        ->layout($articleLayout)
        ->type($articleType)
        ->withTranslations($site->languages)
        ->create();

    expect($article)
        ->toBeInstanceOf(Article::class)
        ->type->name->toBe('Article')
        ->layout->name->toBe('Article');

    get($article->pageUrl->full_url)
        ->assertOk()
        ->assertSeeHtml(e($article->title))
        ->assertSeeHtml(e($blogPage->label));
});

test('article page list tags', function (): void {
    $blogCreator = resolve(BlogCreator::class);

    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();
    $tags = Tag::factory()->count(3)->translate($language)->type(TagTypeEnum::Page)->create();

    $blogPage = $blogCreator->createBlogPage($site);

    $tagsPage = $blogCreator->createTagsPage($site, $blogPage, createWidgets: true);
    $tagPage = $blogCreator->createTagPage($site, $tagsPage);

    $archivesPage = $blogCreator->createArchivesPage($blogPage);
    $archivePage = $blogCreator->createArchivePage($archivesPage);

    $articleType = $blogCreator->createArticlePageType();
    $articleLayout = $blogCreator->createArticleLayout();

    $article = Article::factory()
        ->site($site)
        ->layout($articleLayout)
        ->type($articleType)
        ->withTranslations()
        ->hasAttached($tags)
        ->create();

    $archiveUrl = GenerateArchiveUrl::run(
        $archivePage->pageUrl,
        ArchiveMonthData::fromDate(
            ($article->visible_from ?? $article->created_at) instanceof CarbonImmutable
                ? ($article->visible_from ?? $article->created_at)
                : CarbonImmutable::instance($article->visible_from ?? $article->created_at),
        ),
    );

    expect($article)
        ->toBeInstanceOf(Article::class)
        ->type->name->toBe('Article')
        ->layout->name->toBe('Article')
        ->translation->slug->toBe(str($article->name . '-' . $article->translation->language->locale)->slug()->toString())
        ->pageUrl->url->toBe('/blog/' . $article->translation->slug)
        ->tags->toHaveCount(3);

    get($article->pageUrl->full_url)
        ->assertOk()
        ->assertSeeHtml(e($article->title))
        ->assertSeeHtml(e($blogPage->label))
        ->assertSee($tags[0]->translate('name', $language->code))
        ->assertSeeHtml('href="' . $tags[0]->getUrl($tagPage, $language) . '"')
        ->assertSeeHtml('href="' . $archiveUrl . '"');
});

test('articles pagination', function (): void {
    $blogCreator = resolve(BlogCreator::class);

    $siteDomain = SiteDomain::factory()->default()->create();
    $site = $siteDomain->site;

    $blogPage = $blogCreator->createBlogPage($site, meta: ['limit' => 5]);
    $blogUrl = $blogPage->pageUrl;

    $articleType = $blogCreator->createArticlePageType();
    $articleLayout = $blogCreator->createArticleLayout();

    $articles = Article::factory()
        ->count(12)
        ->site($siteDomain->site)
        ->layout($articleLayout)
        ->type($articleType)
        ->withTranslations($site->languages)
        ->create();

    $orderedArticles = Article::query()->with(['translation'])->whereKey($articles->pluck('id'))->publishedLatest()->get();
    $this->shownArticles = 0;

    expect($blogPage)
        ->toBeInstanceOf(Page::class)
        ->type->name->toBe('Blog')
        ->layout->name->toBe('Blog Posts');

    get($blogUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            'title',
            fn (AssertElement $elm): BaseAssert => $elm->containsText($blogPage->translation->title . ' | ' . $site->title),
        )
        ->assertElementExists(
            'h1',
            fn (AssertElement $elm): BaseAssert => $elm->containsText($blogPage->translation->title),
        )
        ->assertElementExists(
            '.results',
            fn (AssertElement $elm): BaseAssert => $elm->contains('.asset-item', count: 5)
                ->each(
                    '.asset-item',
                    function (AssertElement $elm) use ($orderedArticles): BaseAssert {
                        $this->shownArticles++;

                        return $elm->containsText($orderedArticles[$this->shownArticles - 1]->title);
                    },
                ),
        )
        ->assertElementExists(
            '.pagination',
            fn (AssertElement $elm): BaseAssert => $elm->find(
                '.pagination-info',
                fn (AssertElement $elm): BaseAssert => $elm->has('aria-label', 'Showing 1 to 5 of 12 results'),
            )
                ->contains('.pagination-links__link', count: 3)
                ->find(
                    '.pagination-links__next',
                    fn (AssertElement $elm): BaseAssert => $elm->has('href', $blogUrl->full_url . '/page/2'),
                ),
        );

    get($blogUrl->full_url . '/page/2')
        ->assertOk()
        ->assertElementExists(
            '.results',
            fn (AssertElement $elm): BaseAssert => $elm->contains('.asset-item', count: 5)
                ->each(
                    '.asset-item',
                    function (AssertElement $elm) use ($orderedArticles): BaseAssert {
                        $this->shownArticles++;

                        return $elm->containsText($orderedArticles[$this->shownArticles - 1]->title);
                    },
                ),
        )
        ->assertElementExists(
            '.pagination',
            fn (AssertElement $elm): BaseAssert => $elm->find(
                '.pagination-info',
                fn (AssertElement $elm): BaseAssert => $elm->has('aria-label', 'Showing 6 to 10 of 12 results'),
            )
                ->contains('.pagination-links__link', count: 4)
                ->find(
                    '.pagination-links__prev',
                    fn (AssertElement $elm): BaseAssert => $elm->has('href', $blogUrl->full_url),
                )
                ->find(
                    '.pagination-links__next',
                    fn (AssertElement $elm): BaseAssert => $elm->has('href', $blogUrl->full_url . '/page/3'),
                ),
        );

    // End the assertion chain before starting the next request
    get($blogUrl->full_url . '/page/3')
        ->assertOk()
        ->assertElementExists(
            '.results',
            fn (AssertElement $elm): BaseAssert => $elm->contains('.asset-item', count: 2)
                ->each(
                    '.asset-item',
                    function (AssertElement $elm) use ($orderedArticles): BaseAssert {
                        $this->shownArticles++;

                        return $elm->containsText($orderedArticles[$this->shownArticles - 1]->title);
                    },
                ),
        )
        ->assertElementExists(
            '.pagination',
            fn (AssertElement $elm): BaseAssert => $elm->find(
                '.pagination-info',
                fn (AssertElement $elm): BaseAssert => $elm->has('aria-label', 'Showing 11 to 12 of 12 results'),
            )
                ->contains('.pagination-links__link', count: 3)
                ->find(
                    '.pagination-links__prev',
                    fn (AssertElement $elm): BaseAssert => $elm->has('href', $blogUrl->full_url . '/page/2'),
                )
                ->doesntContain('.pagination-links__next'),
        );
});
