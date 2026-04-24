<?php

declare(strict_types=1);

use Capell\Blog\Models\Article;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

uses(CreatesAdminUser::class);

it('media list shows media attached to articles', function (): void {
    $article = Article::factory()->create();

    $article->addMedia(__DIR__ . '/../Fixtures/test-image.jpg')
        ->preservingOriginal()
        ->toMediaCollection();

    test()->actingAsAdmin()
        ->get(route('filament.admin.resources.media.index'))
        ->assertSuccessful()
        ->assertSee($article->getMedia()->first()->file_name);
});

it('media list resolves article resource for owner link', function (): void {
    $article = Article::factory()->create();

    $media = $article->addMedia(__DIR__ . '/../Fixtures/test-image.jpg')
        ->preservingOriginal()
        ->toMediaCollection()
        ->first();

    expect($media->model)
        ->toBeInstanceOf(Article::class)
        ->and($media->model->id)
        ->toBe($article->id);

    test()->actingAsAdmin()
        ->get(route('filament.admin.resources.media.index'))
        ->assertSuccessful();
});

it('media owner label displays article details', function (): void {
    $article = Article::factory()
        ->state(['name' => 'Test Article Title'])
        ->create();

    $article->addMedia(__DIR__ . '/../Fixtures/test-image.jpg')
        ->preservingOriginal()
        ->toMediaCollection();

    test()->actingAsAdmin()
        ->get(route('filament.admin.resources.media.index'))
        ->assertSuccessful()
        ->assertSee('Article');
});
