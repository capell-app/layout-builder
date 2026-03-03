<?php

declare(strict_types=1);

use Capell\Blog\Enums\TagTypeEnum;
use Capell\Blog\Models\Article;
use Capell\Blog\Models\Tag;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Core\Models\Site;
use Capell\Tests\Fixtures\Models\User;
use Capell\Tests\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

test('article page with layout', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $language = $site->language;
    $user = User::factory()->create();
    $blogCreator = resolve(BlogCreator::class);
    $blogCreator->createTagPage($site);

    $tags = Tag::factory()->count(3)->translate($language)->type(TagTypeEnum::Page)->create();
    $articles = Article::factory()->count(3)->site($site)->publisher($user)->withTranslations()->create();
    $page = $articles->get(1);
    $page->tags()->attach($tags);
    $pageTags = $page->tags()->ordered()->get();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            'title',
            fn (AssertElement $elm): BaseAssert => $elm->containsText($page->translation->title . ' | ' . $site->title),
        )
        ->assertElementExists(
            'h1',
            fn (AssertElement $elm): BaseAssert => $elm->containsText($page->translation->title),
        )
        ->assertElementExists(
            'time.published-date',
            fn (AssertElement $elm): BaseAssert => $elm->has('datetime', $page->published_at->toW3cString()),
        )
        ->assertElementExists(
            '.article-meta',
            fn (AssertElement $elm): BaseAssert => $elm->find(
                '.page-author',
                fn (AssertElement $elm): BaseAssert => $elm->containsText($user->name),
            )
                ->find(
                    '.article-tags',
                    fn (AssertElement $elm): BaseAssert => $elm->contains('.tag-item', count: 3)
                        ->each(
                            '.tag-item',
                            fn (AssertElement $elm, int $index): BaseAssert => $elm->containsText($pageTags[$index]->translate('name', $language->code)),
                        ),
                ),
        )
        ->assertElementExists(
            '.neighbor-links',
            fn (AssertElement $elm): BaseAssert => $elm->contains('.neighbor-link', 2)
                ->each(
                    '.neighbor-link',
                    fn (AssertElement $link, int $index): BaseAssert => $link->find(
                        'a',
                        fn (AssertElement $link): BaseAssert => $link->has(
                            'href',
                            $articles->get($index === 0 ? 0 : 2)->pageUrl->full_url,
                        ),
                    ),
                ),
        );
});
