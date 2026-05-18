<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\Core\Facades\CapellCore;
use Capell\LayoutBuilder\Models\Block;
use Capell\LayoutBuilder\Models\BlockAsset;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

class LayoutModelRegistrar
{
    /** @var list<class-string> */
    private const array MODELS = [
        Block::class,
        BlockAsset::class,
    ];

    public static function register(): void
    {
        CapellCore::registerModels(self::MODELS);

        Relation::morphMap(
            collect(self::MODELS)
                ->mapWithKeys(fn (string $modelClass): array => [Str::snake(class_basename($modelClass)) => $modelClass])
                ->merge([
                    'block' => Block::class,
                    'block_asset' => BlockAsset::class,
                ])
                ->all(),
        );
    }
}
