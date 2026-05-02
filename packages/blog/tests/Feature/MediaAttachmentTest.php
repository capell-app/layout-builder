<?php

declare(strict_types=1);

use Capell\Blog\Models\Article;

it('article can have media attached', function (): void {
    $article = Article::factory()->create();

    $article->addMedia(__DIR__ . '/../Fixtures/test-image.jpg')
        ->preservingOriginal()
        ->toMediaCollection();

    expect($article->getMedia()->count())
        ->toBe(1);
});

it('media attached to article stores correct model type', function (): void {
    $article = Article::factory()->create();

    $article->addMedia(__DIR__ . '/../Fixtures/test-image.jpg')
        ->preservingOriginal()
        ->toMediaCollection();

    $media = $article->getMedia()->first();

    expect($media->model_type)
        ->toBe('article')
        ->and($media->model_id)
        ->toBe($article->id);
});

it('media model relation returns the article', function (): void {
    $article = Article::factory()->create();

    $article->addMedia(__DIR__ . '/../Fixtures/test-image.jpg')
        ->preservingOriginal()
        ->toMediaCollection();

    $media = $article->getMedia()->first();
    $relatedModel = $media->model;

    expect($relatedModel)
        ->toBeInstanceOf(Article::class)
        ->and($relatedModel->id)
        ->toBe($article->id);
});
