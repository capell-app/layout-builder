<?php

declare(strict_types=1);

namespace Capell\Blog\Observers;

use Capell\Blog\Models\Article;
use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\QueryBuilder;

class ArticleObserver
{
    public function creating(Article $article): void
    {
        $article->setIsCurrent(true);
        $article->setUuid(null);
        $article->generateUuid();
        $article->setPublisher();

        // Publish unless explicitly marked as draft (false)
        if ($article->isClean('is_published') === true && $article->getIsPublished() === null) {
            $article->setAttribute($article->getPublishedAtColumn(), now());
            $article->setAttribute($article->getIsPublishedColumn(), true);
            $article->setCurrent();
        }
    }

    public function saving(Article $article): void
    {
        if ($article->visible_from?->isNowOrFuture()) {
            $article->is_published = true;
        }
    }

    public function updating(Article $article): void
    {
        $article->newRevision();
    }

    public function saved(Article $article): void
    {
        $this->clearCache();
    }

    public function publishing(Article $article): void
    {
        $article->setLive();
    }

    public function replicating(Article $article): void
    {
        $article->uuid = null;
        $article->generateUuid();
    }

    public function deleted(Article $article): void
    {
        if ($article->isPublished()) {
            $article->revisions()->delete();
        }

        $this->clearCache();
    }

    public function restored(Article $article): void
    {
        Model::withoutEvents(function () use ($article): void {
            $eloquent = $article;
            $column = $eloquent->getDeletedAtColumn();

            /** @var QueryBuilder $builder */
            $builder = $eloquent->newQueryWithoutScopes();

            $builder
                ->whereDescendantOf($eloquent->getKey())
                ->update([$column => null]);
        });

        Model::withoutEvents(function () use ($article): void {
            $article->revisions()->getQuery()->update([$article->getDeletedAtColumn() => null]);
        });

        $this->clearCache();
    }

    private function clearCache(): void
    {
        // TODO
    }
}
