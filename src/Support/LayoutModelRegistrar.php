<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Widget;
use Capell\Core\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

class LayoutModelRegistrar
{
    /** @var list<class-string> */
    private const MODELS = [
        Widget::class,
        WidgetAsset::class,
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
