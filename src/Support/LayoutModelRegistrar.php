<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\Core\Facades\CapellCore;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Models\WidgetWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

class LayoutModelRegistrar
{
    /** @var list<class-string> */
    private const array MODELS = [
        Widget::class,
        WidgetAsset::class,
        WidgetWidget::class,
    ];

    public static function register(): void
    {
        CapellCore::registerModels(self::MODELS);

        /** @var array<string, class-string<Model>> $morphMap */
        $morphMap = collect(self::MODELS)
            ->mapWithKeys(fn (string $modelClass): array => [Str::snake(class_basename($modelClass)) => $modelClass])
            ->merge([
                'widget' => Widget::class,
                'widget_asset' => WidgetAsset::class,
                'widget_widget' => WidgetWidget::class,
            ])
            ->all();

        Relation::morphMap($morphMap);
    }
}
