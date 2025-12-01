<?php

declare(strict_types=1);

use Capell\Admin\Filament\Actions\Page\CreatePageModalAction;
use Capell\Blog\Database\Factories\ArticlePageFactory;
use Capell\Blog\Filament\Resources\Articles\Pages\EditArticle;
use Capell\Blog\Filament\Resources\Articles\Pages\ListArticles;
use Capell\Blog\Services\BlogCreator;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageTranslation;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Tests\Fixtures\Support\Concerns\CreatesAdminUser;
use Illuminate\Support\Str;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('widget');

beforeEach(function (): void {
    test()->actingAsAdmin();
    Layout::query()->create(['key' => 'article', 'name' => 'Article Layout']);
});

describe('from edit article', function (): void {
    test('can create new article', function (): void {
        $page = (new ArticlePageFactory)->create();
        $newData = (new ArticlePageFactory)->recycle($page->site)->make();

        $slug = str($newData->name)->slug()->toString();

        livewire(EditArticle::class, ['record' => $page->getRouteKey()])
            ->assertSuccessful()
            ->mountAction(CreatePageModalAction::class)
            ->fillForm([
                'type_id' => $newData->type_id,
                'site_id' => $newData->site_id,
            ])
            ->callMountedAction()
            ->set('mountedActions.0.data.translations', [
                (string) Str::uuid() => [
                    'title' => $newData->name,
                    'language_id' => $page->site->language_id,
                    'slug' => $slug,
                ],
            ])
            ->callMountedAction()
            ->assertHasNoFormErrors();

        assertDatabaseHas(Page::class, [
            'name' => $newData->name,
        ]);

        assertDatabaseHas(PageTranslation::class, [
            'title' => $newData->name,
            'slug' => $slug,
            'language_id' => $page->site->language_id,
        ]);

        assertDatabaseHas(PageUrl::class, [
            'url' => '/' . $slug,
        ]);
    });

    test('required fields are required', function (): void {
        $page = (new ArticlePageFactory)->create();

        livewire(EditArticle::class, ['record' => $page->getRouteKey()])
            ->assertSuccessful()
            ->callAction(CreatePageModalAction::class, [
                'translations' => [
                    'abc' => [
                        'language_id' => $page->site->language_id,
                        'title' => '',
                        'slug' => '',
                    ],
                ],
            ])
            ->assertHasFormErrors([
                'translations.abc.title' => 'required',
                'translations.abc.slug' => 'required',
            ]);
    });
});

describe('from list article', function (): void {
    test('can create new article', function (): void {
        $blogCreator = resolve(BlogCreator::class);
        $type = $blogCreator->createArticlePageType();

        $language = Language::factory()->create();

        $site = Site::factory()
            ->recycle($language)
            ->hasSiteDomains()
            ->create();

        $newData = Page::factory()->recycle($site)->type($type)->make();

        $blogCreator->createArticleLayout();

        $blogPage = $blogCreator->createBlogPage($site);

        livewire(ListArticles::class)
            ->assertSuccessful()
            ->mountAction('create')
            ->set('mountedActions.0.data.translations', [])
            ->fillForm([
                'site_id' => $site->id,
                'name' => $newData->name,
            ])
            ->set(
                'mountedActions.0.data.translations',
                $site->languages->mapWithKeys(fn ($language): array => [
                    (string) Str::uuid() => [
                        'language_id' => $language->getKey(),
                        'title' => $newData->name,
                        'slug' => str($newData->name)->slug()->toString(),
                    ],
                ])
                    ->toArray(),
            )
            ->assertSchemaStateSet([
                'name' => $newData->name,
                'type_id' => $type->id,
                'site_id' => $site->id,
            ])
            ->callMountedAction()
            ->assertHasNoFormErrors();

        assertDatabaseHas(Page::class, [
            'name' => $newData->name,
            'parent_id' => $blogPage->getKey(),
        ]);
    });

    test('can create new article from list page', function (): void {
        $blogCreator = resolve(BlogCreator::class);

        $type = $blogCreator->createArticlePageType();
        $layout = $blogCreator->createArticleLayout();

        $language = Language::factory()->create();
        $site = Site::factory()->recycle($language)->hasSiteDomains()->create();

        $newData = (new ArticlePageFactory)->make();

        livewire(ListArticles::class)
            ->assertSuccessful()
            ->mountAction('create')
            ->set('mountedActions.0.data.translations', [])
            ->fillForm([
                'name' => $newData->name,
            ])
            ->set(
                'mountedActions.0.data.translations',
                $site->languages->mapWithKeys(fn ($language): array => [
                    (string) Str::uuid() => [
                        'language_id' => $language->getKey(),
                        'title' => $newData->name,
                        'slug' => str($newData->name)->slug()->toString(),
                    ],
                ])
                    ->toArray(),
            )
            ->assertSchemaStateSet([
                'name' => $newData->name,
                'layout_id' => $layout->id,
                'type_id' => $type->id,
                'site_id' => $site->id,
            ])
            ->callMountedAction()
            ->assertHasNoFormErrors();

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
            ->callAction(CreatePageModalAction::class, [
                'name' => '',
            ])
            ->assertHasFormErrors([
                'name' => 'required',
            ]);
    });
});
