<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Assets;

use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Actions\ResolvePublicWidgetAssetsAction;
use Capell\LayoutBuilder\Contracts\PublicLayoutAssetMembership;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutWidgetData;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;
use Illuminate\Database\Eloquent\Model;
use Override;

final class LayoutBuilderPublicLayoutAssetMembership implements PublicLayoutAssetMembership
{
    #[Override]
    public function contains(Model $asset, Page $page, Layout $layout, Language $language): bool
    {
        $assetId = $asset->getKey();
        if (! is_int($assetId) && ! is_string($assetId)) {
            return false;
        }
        $layoutId = $layout->getKey();
        if ((! is_int($layoutId) && ! is_string($layoutId)) || (string) $page->layout_id !== (string) $layoutId) {
            return false;
        }

        $containers = is_array($layout->containers) ? $layout->containers : [];
        $loader = resolve(LayoutLoader::class);
        foreach ($containers as $containerKey => $container) {
            foreach (LayoutWidgetData::fromContainer(is_array($container) ? $container : []) as $entry) {
                $key = LayoutWidgetData::key($entry);
                if ($key === null) {
                    continue;
                }
                $occurrence = LayoutWidgetData::occurrence($entry);
                $widget = $loader->getLayoutWidget($layout, $key, $language, $page, (string) $containerKey, $occurrence);
                if (! $widget instanceof Widget) {
                    continue;
                }
                foreach (ResolvePublicWidgetAssetsAction::run($widget, $page, $language, (string) $containerKey, $occurrence) as $attachment) {
                    if ($attachment->asset_type === $asset->getMorphClass() && (string) $attachment->asset_id === (string) $assetId) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
