<?php

declare(strict_types=1);

use Capell\Blog\Models\Article;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Tags\Enums\TagTypeEnum;
use Capell\Tags\Models\Tag;
use Capell\Tests\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

test('tag page list articles by tag', function (): void {
    $blogCreator = resolve(BlogCreator::class);

    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();

    $blogPage = $blogCreator->createBlogPage($site);
    $tagsPage = $blogCreator->createTagsPage($site, $blogPage, createWidgets: true);
    $tagPage = $blogCreator->createTagPage($site, $tagsPage);

    $tag = Tag::factory()->translate($language)->type(TagTypeEnum::Page)->create();

    Article::factory()
        ->site($site)
        ->withTranslations()
        ->hasAttached($tag)
        ->forEachSequence(
            ['visible_from' => '2023-01-01'],
            ['visible_from' => '2023-02-01'],
            ['visible_from' => '2023-03-01'],
            ['visible_from' => '2023-04-01'],
            ['visible_from' => '2023-05-01'],
        )
        ->create();

    $articles = Article::query()
        ->with(['translation', 'pageUrl.siteDomain'])
        ->whereRelation('site', 'id', $site->getKey())
        ->publishedLatest()
        ->get();

    $title = trans($tagPage->translation->title, ['tag_name' => $tag->translate('name', $language->code)]);

    $containers = $tagPage->layout->containers;
    $containerWidgets = collect($containers)->pluck('widgets.*.widget_key')->flatten()->filter()->toArray();

    expect($tagPage)
        ->translation->title->toBe(':Tag_name Articles')
        ->and($containerWidgets)->toContain('breadcrumbs')
        ->and($articles)->toHaveCount(5);

    get($tag->getUrl($tagPage, $language))
        ->assertOk()
        ->assertDontSeeText(':Tag_name Articles')
        ->assertElementExists(
            'title',
            fn (AssertElement $elm): BaseAssert => $elm->containsText($title . ' | ' . $site->title),
        )
        ->assertElementExists(
            'h1',
            fn (AssertElement $elm): BaseAssert => $elm->containsText($title),
        )
        ->assertElementExists(
            '.results',
            fn (AssertElement $elm): BaseAssert => $elm->doesntContain('.no-results')
                ->contains('.asset-index', count: $articles->count())
                ->each(
                    '.asset-index',
                    function (AssertElement $titleElm, int $index) use ($articles): BaseAssert {
                        $article = $articles->get($index);

                        return $titleElm->containsText($article->translation->title)
                            ->find(
                                'a',
                                fn (AssertElement $linkElm): BaseAssert => $linkElm->has(
                                    'href',
                                    $article->pageUrl->full_url,
                                ),
                            );
                    },
                ),
        );
});

test('tag page resolves site tag before global tag with same slug', function (): void {
    $blogCreator = resolve(BlogCreator::class);

    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();

    $blogPage = $blogCreator->createBlogPage($site);
    $tagsPage = $blogCreator->createTagsPage($site, $blogPage, createWidgets: true);
    $tagPage = $blogCreator->createTagPage($site, $tagsPage);

    $slug = 'shared-topic';

    $globalTag = Tag::factory()
        ->type(TagTypeEnum::Page)
        ->site(null)
        ->create([
            'name' => [$language->code => 'Global Topic'],
            'slug' => [$language->code => $slug],
        ]);

    $siteTag = Tag::factory()
        ->type(TagTypeEnum::Page)
        ->site($site)
        ->create([
            'name' => [$language->code => 'Site Topic'],
            'slug' => [$language->code => $slug],
        ]);

    $globalArticle = Article::factory()
        ->site($site)
        ->withTranslations($site->languages, ['title' => 'Global Tagged Article'])
        ->hasAttached($globalTag)
        ->create(['visible_from' => '2023-01-01']);

    $siteArticle = Article::factory()
        ->site($site)
        ->withTranslations($site->languages, ['title' => 'Site Tagged Article'])
        ->hasAttached($siteTag)
        ->create(['visible_from' => '2023-02-01']);

    get($siteTag->getUrl($tagPage, $language))
        ->assertOk()
        ->assertSeeText('Site Topic Articles')
        ->assertSeeText($siteArticle->translation->title)
        ->assertDontSeeText('Global Topic Articles')
        ->assertDontSeeText($globalArticle->translation->title);
});
