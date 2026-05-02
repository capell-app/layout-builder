<?php

declare(strict_types=1);

namespace Capell\Blog\Support;

use Capell\Blog\Models\Article;
use Capell\Core\Data\PageVariationData;
use Capell\Core\Facades\CapellCore;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

class BlogModelRegistrar
{
    /** @var list<class-string> */
    private const MODELS = [
        Article::class,
    ];

    public static function register(): void
    {
        CapellCore::registerModels(self::MODELS);

        CapellCore::registerPageVariation(
            new PageVariationData(
                name: 'article',
                model: Article::class,
                resourceName: 'article',
            ),
        );

        Relation::morphMap(
            collect(self::MODELS)
                ->mapWithKeys(fn (string $modelClass): array => [Str::snake(class_basename($modelClass)) => $modelClass])
                ->all(),
        );
    }
}
