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

uses(TestingFrontend::class);

beforeEach(function (): void {
    config(['capell-core.disable_cache_save_keys' => [CacheEnum::Pages->value . '-*']]);
});

test('blog page lists articles', function (): void {
    $blogCreator = resolve(BlogCreator::class);

    $siteDomain = SiteDomain::factory()->default()->create();
    $site = $siteDomain->site;

    $blogPage = $blogCreator->createBlogPage($site);
    $blogUrl = $blogPage->url;

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
        ->parent($blogPage)
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
    $blogUrl = $blogPage->url;

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
        ->parent($blogPage)
        ->withTranslations($site->languages)
        ->create();

    expect($article)
        ->toBeInstanceOf(Page::class)
        ->type->name->toBe('Article')
        ->layout->name->toBe('Article')
        ->parent->name->toBe('Blog');

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
        ->parent($blogPage)
        ->withTranslations()
        ->hasAttached($tags)
        ->create();

    $archiveUrl = GenerateArchiveUrl::run(
        $archivePage->url,
        ArchiveMonthData::fromDate(
            ($article->publish_from ?? $article->created_at) instanceof CarbonImmutable
                ? ($article->publish_from ?? $article->created_at)
                : CarbonImmutable::instance($article->publish_from ?? $article->created_at),
        ),
    );

    get($article->pageUrl->full_url)
        ->assertOk()
        ->assertSeeHtml(e($article->title))
        ->assertSeeHtml(e($blogPage->label))
        ->assertSee($tags[0]->translate('name', $language->code))
        ->assertSeeHtml('href="' . $tags[0]->getUrl($tagPage, $language) . '"')
        ->assertSeeHtml('href="' . $archiveUrl . '"');
});
