<?php

declare(strict_types=1);

namespace Capell\Layout\Services\Creator;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Translation;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LayoutLoader
{
    public static function getLayout(int $id): ?Layout
    {
        $key = 'layout-' . $id;

        $fromCache = true;

        $layout = CapellCore::rememberCache($key, function () use ($id, &$fromCache): ?Layout {
            $fromCache = false;

            // @phpstan-ignore-next-line
            return Layout::with('layoutWidgets')->find($id); // TODO fix error larastan.relationExistence even though it's added manually
        }) ?: null;

        if ($fromCache && $layout) {
            event('eloquent.retrieved: ' . Layout::class, $layout);

            $layout->layoutWidgets->each(function (Widget $widget): void {
                event('eloquent.retrieved: ' . $widget::class, $widget);
            });
        }

        return $layout;
    }

    public static function getLayoutWidget(
        Layout $layout,
        string $widgetKey,
        Language $language,
        ?Page $page,
        string $containerKey,
        int $occurrence,
    ): ?Widget {
        $key = sprintf(
            'layout-%d-widget-%s-lang-%d-container-%s-occurrence-%d',
            $layout->id,
            $widgetKey,
            $language->id,
            $containerKey,
            $occurrence,
        )
            . ($page instanceof Page ? '-page-' . $page->id : '');

        $fromCache = true;

        $widget = CapellCore::rememberCache(
            $key,
            function () use ($layout, $widgetKey, $language, $page, $containerKey, $occurrence, &$fromCache): ?Widget {
                $fromCache = false;

                /** @var Widget $widget */
                $widget = $layout->layoutWidgets->firstWhere('key', $widgetKey);

                if (! $widget) {
                    return null;
                }

                $widget->load([
                    'type',
                    'image',
                    'backgroundImage',
                    'media',
                    'translation' => fn (BuilderContract $query) => $query->where('language_id', $language->id),
                ]);

                $widget->translation?->setRelation('language', $language);

                $layoutWidget = clone $widget;

                $resourceRelationsCallback = function (MorphTo $morphTo) use ($language): void {
                    $morphTo->morphWith([
                        Content::class => Content::getMorphRelations($language),
                        Page::class => Page::getMorphRelations($language),
                    ]);
                };

                $widgetAssets = $layoutWidget->pageAssets($page, $containerKey, $occurrence)
                    ->whereHas('asset')
                    ->with('asset', $resourceRelationsCallback)
                    ->ordered()
                    ->get();

                if ($widgetAssets->isEmpty()) {
                    $widgetAssets = $layoutWidget->widgetAssets()
                        ->whereHas('asset')
                        ->with('asset', $resourceRelationsCallback)
                        ->ordered()
                        ->get();
                }

                $layoutWidget->setRelation('assets', $widgetAssets);

                return $layoutWidget;
            },
        ) ?: null;

        if ($fromCache && $widget) {
            event('eloquent.retrieved: ' . Widget::class, $widget);

            $widget->assets->each(function (WidgetAsset $resource): void {
                event('eloquent.retrieved: ' . $resource::class, $resource);
            });

            if ($widget->translation) {
                event('eloquent.retrieved: ' . Translation::class, $widget->translation);
                event('eloquent.retrieved: ' . Language::class, $widget->translation->language);
            }
        }

        return $widget;
    }
}
