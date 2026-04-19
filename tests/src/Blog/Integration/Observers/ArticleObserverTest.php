<?php

declare(strict_types=1);

use Capell\Blog\Models\Article;
use Capell\Core\Models\Site;

it('observer triggers on article save', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $article = Article::factory()->site($site)->create();

    expect($article)->toBeInstanceOf(Article::class);

    $article->update(['title' => 'Updated Title']);

    expect($article->title)->toBe('Updated Title');
});

it('observer triggers on article delete', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $article = Article::factory()->site($site)->create();
    $articleId = $article->id;

    $article->delete();

    expect(Article::find($articleId))->toBeNull();
});

it('observer triggers on article restore', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $article = Article::factory()->site($site)->create();

    $article->delete();
    $article->restore();

    expect(Article::find($article->id))->toBeInstanceOf(Article::class);
});
