<?php

declare(strict_types=1);

namespace Capell\Tags\Support;

use Capell\Core\Facades\CapellCore;
use Capell\Tags\Models\Tag;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

class TagModelRegistrar
{
    /** @var list<class-string> */
    private const MODELS = [
        Tag::class,
    ];

    public static function register(): void
    {
        CapellCore::registerModels(self::MODELS);

        Relation::morphMap(
            collect(self::MODELS)
                ->mapWithKeys(fn (string $modelClass): array => [Str::snake(class_basename($modelClass)) => $modelClass])
                ->all(),
        );
    }
}
