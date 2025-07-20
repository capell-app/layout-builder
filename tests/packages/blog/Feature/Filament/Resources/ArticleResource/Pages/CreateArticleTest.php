<?php

declare(strict_types=1);

use Capell\Admin\Filament\Actions\Page\CreatePageAction;
use Capell\Blog\Database\Factories\ArticlePageFactory;
use Capell\Blog\Filament\Resources\ArticleResource\Pages\EditArticle;
use Capell\Blog\Filament\Resources\ArticleResource\Pages\ListArticles;
use Capell\Blog\Services\BlogCreator;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageTranslation;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('widget');

beforeEach(function (): void {
    test()->actingAsAdmin();

    // Register BlogServiceProvider
    $this->app->register(Capell\Blog\BlogServiceProvider::class);
});

describe('from edit article', function (): void {
    test('can create new article', function (): void {
        $page = (new ArticlePageFactory())->create();

        $newData = (new ArticlePageFactory())->recycle($page->site)->make();

        $slug = str($newData->name)->slug()->toString();

        livewire(EditArticle::class, ['record' => $page->getRouteKey()])
            ->assertSuccessful()
            ->mountAction(CreatePageAction::class)
            ->setActionData([
                'type_id' => $newData->type_id,
                'site_id' => $newData->site_id,
            ])
            ->set('mountedActionsData.0.translations', [
                0 => [
                    'title' => $newData->name,
                    'language_id' => $page->site->language_id,
                    'slug' => $slug,
                ],
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        assertDatabaseHas(Page::class, [
            'name' => $newData->name,
        ]);

        assertDatabaseHas(PageTranslation::class, [
            'title' => $newData->name,
            'slug' => $slug,
            'language_id' => $page->site->language_id,
        ]);

        assertDatabaseHas(PageUrl::class, [
            'url' => '/'.$slug,
        ]);
    });

    test('required fields are required', function (): void {
        $page = (new ArticlePageFactory())->create();

        livewire(EditArticle::class, ['record' => $page->getRouteKey()])
            ->assertSuccessful()
            ->callAction(CreatePageAction::class, [
                'translations' => [
                    0 => [
                        'title' => '',
                    ],
                ],
            ])
            ->assertHasActionErrors([
                'translations.0.title' => 'required',
            ]);
    });
});

describe('from list article', function (): void {
    test('can create new article', function (): void {
        $type = BlogCreator::createArticlePageType();

        $language = Language::factory()->create();

        $site = Site::factory()
            ->recycle($language)
            ->forLanguage($language)
            ->hasSiteDomains()
            ->hasTranslations()
            ->create();

        $newData = Page::factory()->recycle($site)->type($type)->make();

        $blogPage = BlogCreator::createBlogPage($site);

        livewire(ListArticles::class)
            ->assertSuccessful()
            ->mountAction('create')
            ->set('mountedActionsData.0.translations', [])
            ->setActionData([
                'site_id' => $site->id,
                'name' => $newData->name,
            ])
            ->set(
                'mountedActionsData.0.translations',
                $site->languages->map(fn ($language): array => [
                    'language_id' => $language->getKey(),
                    'title' => $newData->name,
                    'slug' => str($newData->name)->slug()->toString(),
                ])
                    ->toArray()
            )
            ->assertActionDataSet([
                'name' => $newData->name,
                'type_id' => $type->id,
                'site_id' => $site->id,
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        assertDatabaseHas(Page::class, [
            'name' => $newData->name,
            'parent_id' => $blogPage->getKey(),
        ]);
    });

    test('can create new article from list page', function (): void {
        $type = BlogCreator::createArticlePageType();
        $layout = BlogCreator::createArticleLayout();

        $language = Language::factory()->create();
        $site = Site::factory()->recycle($language)->hasSiteDomains()->create();

        $newData = (new ArticlePageFactory())->make();

        livewire(ListArticles::class)
            ->assertSuccessful()
            ->mountAction('create')
            ->set('mountedActionsData.0.translations', [])
            ->setActionData([
                'name' => $newData->name,
            ])
            ->set(
                'mountedActionsData.0.translations',
                $site->languages->map(fn ($language): array => [
                    'language_id' => $language->getKey(),
                    'title' => $newData->name,
                    'slug' => str($newData->name)->slug()->toString(),
                ])
                    ->toArray()
            )
            ->assertActionDataSet([
                'name' => $newData->name,
                'layout_id' => $layout->id,
                'type_id' => $type->id,
                'site_id' => $site->id,
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        assertDatabaseHas(Page::class, [
            'name' => $newData->name,
            'type_id' => $type->id,
            'site_id' => $site->id,
            'layout_id' => $layout->id,
        ]);

        $page = Page::query()
            ->where('name', $newData->name)
            ->first();

        expect($page->type)
            ->group->toBe('article');
    });

    test('required fields are required', function (): void {
        $language = Language::factory()->create();
        Site::factory()->recycle($language)->hasSiteDomains()->create();

        livewire(ListArticles::class)
            ->assertSuccessful()
            ->callAction(CreatePageAction::class, [
                'name' => '',
            ])
            ->assertHasActionErrors([
                'name' => 'required',
            ]);
    });
});
