<?php

declare(strict_types=1);

use Capell\Blog\Enums\BlogLayoutEnum;
use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Blog\Filament\Resources\Articles\Pages\EditArticle;
use Capell\Blog\Filament\Resources\Articles\Pages\ListArticles;
use Capell\Blog\Models\Article;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Support\Str;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('widget');

beforeEach(function (): void {
    test()->actingAsAdmin();
    Layout::query()->create(['key' => BlogLayoutEnum::Article->value, 'name' => 'Article Layout']);
});

describe('from edit article', function (): void {
    test('can create new article', function (): void {
        $article = Article::factory()->create();
        $newData = Article::factory()->recycle($article->site)->make();

        $slug = str($newData->name)->slug()->toString();

        $uuid = (string) Str::uuid();

        livewire(EditArticle::class, ['record' => $article->getRouteKey()])
            ->assertSuccessful()
            ->mountAction('create')
            ->fillForm([
                'name' => $newData->name,
                'type_id' => $newData->type_id,
                'site_id' => $newData->site_id,
            ])
            ->set('mountedActions.0.data.translations', [
                $uuid => [
                    'title' => $newData->name,
                    'language_id' => $article->site->language_id,
                    'meta' => ['slug' => $slug],
                ],
            ])
            ->set('mountedActions.0.data.translations.' . $uuid . '.meta.slug', $slug)
            ->callMountedAction()
            ->assertHasNoFormErrors();

        assertDatabaseHas(Article::class, [
            'name' => $newData->name,
            'type_id' => $newData->type_id,
            'layout_id' => $article->layout_id,
        ]);

        assertDatabaseHas(Translation::class, [
            'title' => $newData->name,
            'meta->slug' => $slug,
            'language_id' => $article->site->language_id,
        ]);

        assertDatabaseHas(PageUrl::class, [
            'url' => '/' . $slug,
        ]);
    });

    test('required fields are required', function (): void {
        $article = Article::factory()->create();

        livewire(EditArticle::class, ['record' => $article->getRouteKey()])
            ->assertSuccessful()
            ->callAction('create', [
                'translations' => [
                    'abc' => [
                        'language_id' => $article->site->language_id,
                        'title' => '',
                        'meta' => [
                            'slug' => '',
                        ],
                    ],
                ],
            ])
            ->assertHasFormErrors([
                'translations.abc.title' => 'required',
                'translations.abc.meta.slug' => 'required',
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

        $newData = Article::factory()->recycle($site)->type($type)->make();

        $blogCreator->createArticleLayout(createWidgets: false);

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
                $site->languages->mapWithKeys(fn (Language $language): array => [
                    (string) Str::uuid() => [
                        'language_id' => $language->getKey(),
                        'title' => $newData->name,
                        'meta' => ['slug' => str($newData->name)->slug()->toString()],
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

        assertDatabaseHas(Article::class, [
            'name' => $newData->name,
        ]);
    });

    test('can create new article from list page', function (): void {
        $blogCreator = resolve(BlogCreator::class);

        $type = $blogCreator->createArticlePageType();
        $layout = $blogCreator->createArticleLayout(createWidgets: false);

        $language = Language::factory()->create();
        $site = Site::factory()->recycle($language)->hasSiteDomains()->create();

        $newData = Article::factory()->make();

        livewire(ListArticles::class)
            ->assertSuccessful()
            ->mountAction('create')
            ->assertSchemaComponentDoesNotExist('type_id')
            ->set('mountedActions.0.data.translations', [])
            ->fillForm([
                'name' => $newData->name,
            ])
            ->set(
                'mountedActions.0.data.translations',
                $site->languages->mapWithKeys(fn (Language $language): array => [
                    (string) Str::uuid() => [
                        'language_id' => $language->getKey(),
                        'title' => $newData->name,
                        'meta' => ['slug' => str($newData->name)->slug()->toString()],
                    ],
                ])
                    ->toArray(),
            )
            ->assertSchemaStateSet([
                'name' => $newData->name,
                'layout_id' => $layout->id,
                'site_id' => $site->id,
            ])
            ->callMountedAction()
            ->assertHasNoFormErrors();

        assertDatabaseHas(Article::class, [
            'name' => $newData->name,
            'site_id' => $site->id,
            'layout_id' => $layout->id,
        ]);

        $article = Article::query()
            ->where('name', $newData->name)
            ->first();

        expect($article->type)
            ->key->toBe(BlogPageTypeEnum::Article->value)
            ->group->toBe('article');
    });

    test('required fields are required', function (): void {
        $language = Language::factory()->create();
        Site::factory()->recycle($language)->hasSiteDomains()->create();

        livewire(ListArticles::class)
            ->assertSuccessful()
            ->callAction('create')
            ->assertHasErrors();
    });
});
