<?php

declare(strict_types=1);

namespace Capell\Blog\Observers;

use Capell\Blog\Actions\ClearBlogContentCacheAction;
use Capell\Blog\Models\Article;

class ArticleObserver
{
    public function saved(Article $article): void
    {
        $this->clearCache($article);
    }

    public function deleted(Article $article): void
    {
        $this->clearCache($article);
    }

    public function restored(Article $article): void
    {
        $this->clearCache($article);
    }

    private function clearCache(Article $article): void
    {
        ClearBlogContentCacheAction::run($article);
    }
}
