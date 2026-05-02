<?php

declare(strict_types=1);

namespace Capell\Blog\Listeners;

use Capell\Blog\Models\Article;
use Capell\Blog\Support\Loader\BlogLoader;
use Capell\Core\Actions\UpdatePageUrlAction;
use Capell\Core\Models\Translation;
use Illuminate\Database\Eloquent\Relations\Relation;

final class ArticleTranslationSavedListener
{
    public function __invoke(Translation $translation): void
    {
        $articleMorphAlias = Relation::getMorphAlias(Article::class);

        if ($articleMorphAlias === Article::class || $translation->translatable_type !== $articleMorphAlias) {
            return;
        }

        /** @var Article $article */
        $article = $translation->translatable;

        $url = BlogLoader::getBlogPageUrl($article->site, $translation->language, fullUrl: false);

        UpdatePageUrlAction::run($article->site, $translation, $url);
    }
}
