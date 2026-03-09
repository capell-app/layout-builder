<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Blog\Enums\TagTypeEnum;
use Capell\Blog\Filament\Resources\Articles\ArticleResource;
use Capell\Blog\Filament\Resources\Articles\Pages\EditArticle;
use Capell\Blog\Models\Article;
use Capell\Blog\Models\Tag;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('page', 'article');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('can render article', function (): void {
    get(ArticleResource::getUrl('edit', [
        'record' => Article::factory()->create(),
    ]))->assertSuccessful();
});

test('can not render article', function (): void {
    test()->withoutExceptionHandling();

    get(PageResource::getUrl('edit', [
        'record' => Article::factory()->create(),
    ]));
})->throws(ModelNotFoundException::class);

test('can not render page', function (): void {
    test()->withoutExceptionHandling();

    get(ArticleResource::getUrl('edit', [
        'record' => Page::factory()->create(),
    ]));
})->throws(ModelNotFoundException::class);

it('can save', function (): void {
    $site = Site::factory()->hasSiteDomains()->create();
    $languages = $site->siteDomains->map->language_id;

    $page = Article::factory()->recycle($site)->create();

    $languages->each(function (int $languageId) use ($page): void {
        $page->translations()->save(Translation::factory()->slug(Str::slug($page->name . ' ' . $languageId))->make([
            'language_id' => $languageId,
            'title' => Str::title($page->name . ' ' . $languageId),
        ]));
    });

    $page->refresh();

    $newData = Article::factory()->site($site)->make();

    livewire(EditArticle::class, [
        'record' => $page->getRouteKey(),
    ])
        ->assertSuccessful()
        ->assertSchemaStateSet([
            'name' => $page->name,
            'layout_id' => $page->layout->getKey(),
            'type_id' => $page->type->getKey(),
            'site_id' => $page->site->getKey(),
        ])
        ->fillForm([
            'name' => $newData->name,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($page->refresh())
        ->name->toBe($newData->name);
});

it('can delete', function (): void {
    $article = Article::factory()->create();

    livewire(EditArticle::class, [
        'record' => $article->getRouteKey(),
    ])
        ->assertSuccessful()
        ->callAction(DeleteAction::class)
        ->assertHasNoFormErrors();

    assertSoftDeleted($article, ['id' => $article->id]);
});

test('can edit article tags', function (): void {
    $tags = Tag::factory()->count(3)->type(TagTypeEnum::Page)->create();
    $article = Article::factory()->hasAttached($tags->first())->withTranslations()->create();

    livewire(EditArticle::class, [
        'record' => $article->getRouteKey(),
    ])
        ->assertSuccessful()
        ->assertFormFieldExists('tags')
        ->fillForm([
            'tags' => $tags->pluck('name')->toArray(),
        ])
        ->call('save')
        ->assertSet('record', fn (Model $article): bool => $article instanceof Article)
        ->assertHasNoFormErrors();

    expect($article->refresh())->tags->toHaveCount(3);
});
