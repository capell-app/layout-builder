<?php

declare(strict_types=1);

namespace Capell\Blog\Support;

use Capell\Blog\Enums\ModelEnum;
use Capell\Blog\Models\Article;
use Capell\Core\Data\PageVariationData;
use Capell\Core\Facades\CapellCore;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

class BlogModelRegistrar
{
    public static function register(): void
    {
        CapellCore::registerModels(ModelEnum::cases());

        CapellCore::registerPageVariation(
            new PageVariationData(
                name: 'article',
                model: Article::class,
                resourceName: 'article',
            ),
        );

        Relation::morphMap(
            collect(ModelEnum::cases())
                ->mapWithKeys(fn (ModelEnum $model): array => [Str::snake($model->name) => $model->value])
                ->all(),
        );
    }
}
