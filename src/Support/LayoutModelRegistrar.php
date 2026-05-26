<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Models\WidgetBlock;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

class LayoutModelRegistrar
{
    /** @var list<class-string> */
    private const array MODELS = [
        Widget::class,
        WidgetAsset::class,
        WidgetBlock::class,
    ];

    public static function register(): void
    {
        Layout::addFillable(['widgets']);
        Layout::addCasts(['widgets' => 'array']);

        CapellCore::registerModels(self::MODELS);

        Relation::morphMap(
            collect(self::MODELS)
                ->mapWithKeys(fn (string $modelClass): array => [Str::snake(class_basename($modelClass)) => $modelClass])
                ->merge([
                    'block' => Widget::class,
                    'block_asset' => WidgetAsset::class,
                    'widget_block' => WidgetBlock::class,
                ])
                ->all(),
        );
    }
}
