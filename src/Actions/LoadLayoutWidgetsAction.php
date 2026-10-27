<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Data\LoadedLayoutWidgetData;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutWidgetData;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Preload a layout's widgets through the request-scoped loader and return every
 * placement whose widget resolved, so companion render data contributors share
 * one loading path without reaching into the loader themselves.
 *
 * @method static list<LoadedLayoutWidgetData> run(Layout $layout, Language $language, ?Pageable $page)
 */
final class LoadLayoutWidgetsAction
{
    use AsFake;
    use AsObject;

    /** @return list<LoadedLayoutWidgetData> */
    public function handle(Layout $layout, Language $language, ?Pageable $page): array
    {
        $loader = resolve(LayoutLoader::class);
        $loader->preloadLayoutWidgets($layout, $language, $page);
        $widgets = [];

        foreach ($layout->containers ?? [] as $containerKey => $container) {
            foreach (LayoutWidgetData::fromContainer(is_array($container) ? $container : []) as $placement) {
                $key = LayoutWidgetData::key($placement);
                if ($key === null) {
                    continue;
                }

                $occurrence = LayoutWidgetData::occurrence($placement);
                $widget = $loader->getLayoutWidget($layout, $key, $language, $page, (string) $containerKey, $occurrence);
                if ($widget instanceof Widget) {
                    $widgets[] = new LoadedLayoutWidgetData((string) $containerKey, $key, $occurrence, $widget);
                }
            }
        }

        return $widgets;
    }
}
