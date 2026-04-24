<?php

declare(strict_types=1);

use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Blog\Models\Article;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Mosaic\Actions\AddHeroWidgetToLayoutAction;
use Capell\Mosaic\Actions\CreateHeroContentTypeAction;
use Capell\Mosaic\Actions\CreateHeroWidgetAction;
use Capell\Mosaic\Enums\LayoutTypeEnum;
use Capell\Mosaic\Models\Widget;
use Capell\Mosaic\Support\Creator\DemoCreator;
use Illuminate\Console\Command;
use Mockery\MockInterface;

use function Pest\Laravel\artisan;

it('adds hero meta to blog and article pages when blog package is installed', function (): void {
    AddHeroWidgetToLayoutAction::shouldRun()->once();
    CreateHeroContentTypeAction::shouldRun()->once()->andReturn(Type::factory()->type(LayoutTypeEnum::Section)->create());

    $heroWidget = Widget::factory()->make();
    CreateHeroWidgetAction::shouldRun()->twice()->andReturn($heroWidget);

    $demoCreator = mock(DemoCreator::class, function (DemoCreator&MockInterface $mock): void {
        $mock->shouldReceive('createContentsWidget')->once();
    });

    app()->instance(DemoCreator::class, $demoCreator);

    $languages = Language::factory(2)->create();
    $site = Site::factory()
        ->language($languages[0])
        ->state(['name' => 'DemoSite'])
        ->withTranslations($languages)
        ->create();

    Page::factory()->site($site)->home()->withTranslations()->create();

    $blogCreator = resolve(BlogCreator::class);
    $blogCreator->setup($site);

    $articlePage = Article::factory()->site($site)->withTranslations($languages)->create();

    $blogPage = Page::query()->whereRelation('type', 'key', BlogPageTypeEnum::Blog->value)->firstOrFail();

    foreach ($blogPage->translations as $blogTranslation) {
        $meta = $blogTranslation->meta;

        expect($meta)->not()->toHaveKey('hero');
    }

    foreach ($articlePage->translations as $articleTranslation) {
        $meta = $articleTranslation->meta;

        expect($meta)->not()->toHaveKey('hero');
    }

    artisan('capell:hero-demo --sites=DemoSite')
        ->expectsOutput('Demo hero content has been successfully created for site: DemoSite')
        ->expectsOutput('Hero demo content inserted successfully.')
        ->assertExitCode(Command::SUCCESS);

    $expectedBlogHero = '<h1>' . __('capell-blog::generic.latest_articles') . '</h1><p>' . __('capell-blog::generic.blog_intro') . '</p>';

    foreach ($blogPage->fresh()->translations as $blogTranslation) {
        expect($blogTranslation->meta)->hero->toBe($expectedBlogHero);
    }

    $freshArticlePage = $articlePage->fresh();

    foreach ($freshArticlePage->translations as $articleTranslation) {
        expect($articleTranslation->meta)->hero->toBe('<h1>' . $articleTranslation->title . '</h1>');
    }
});
