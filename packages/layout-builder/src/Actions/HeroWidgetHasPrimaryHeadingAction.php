<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Frontend\Facades\Frontend;
use Capell\LayoutBuilder\Models\Widget;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static bool run(Widget $widget, Pageable $page)
 */
class HeroWidgetHasPrimaryHeadingAction
{
    use AsObject;

    public function handle(Widget $widget, Pageable $page): bool
    {
        $hasPrimaryHeading = false;

        $content = null;

        if ($widget->assets->isNotEmpty()) {
            $firstAssetTranslation = $widget->assets->first()?->asset->translation;

            if ($firstAssetTranslation) {
                if ($firstAssetTranslation->title) {
                    $hasPrimaryHeading = true;
                } elseif ($firstAssetTranslation->content) {
                    $content = $firstAssetTranslation->content;
                }
            }
        } else {
            $content = $page->translation->meta['hero'] ?? null;
        }

        if (! $hasPrimaryHeading && filled($content)) {
            $hasPrimaryHeading = preg_match('/<h1\b[^>]*>/i', (string) $content) === 1;
        }

        if ($hasPrimaryHeading) {
            Frontend::setFrontendData('has_primary_heading', true);
        }

        return $hasPrimaryHeading;
    }
}
