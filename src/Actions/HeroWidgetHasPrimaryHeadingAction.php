<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Translation;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static bool run(Widget $widget, Pageable $page)
 */
class HeroWidgetHasPrimaryHeadingAction
{
    use AsFake;
    use AsObject;

    public function handle(Widget $widget, Pageable $page): bool
    {
        $hasPrimaryHeading = false;

        $content = null;

        $assets = $this->loadedRelation($widget, 'assets');

        if ($assets instanceof Collection && $assets->isNotEmpty()) {
            $firstWidgetAsset = $assets->first();
            $firstAsset = $firstWidgetAsset instanceof Model ? $this->loadedRelation($firstWidgetAsset, 'asset') : null;
            $firstAssetTranslation = $firstAsset instanceof Model ? $this->loadedRelation($firstAsset, 'translation') : null;

            if ($firstAssetTranslation instanceof Translation) {
                if ($firstAssetTranslation->title !== null && $firstAssetTranslation->title !== '') {
                    $hasPrimaryHeading = true;
                } elseif ($firstAssetTranslation->content !== null && $firstAssetTranslation->content !== '') {
                    $content = $firstAssetTranslation->content;
                }
            }
        } else {
            $pageTranslation = $page instanceof Model ? $this->loadedRelation($page, 'translation') : null;
            $content = data_get($pageTranslation, 'meta.hero');
        }

        if (! $hasPrimaryHeading && filled($content)) {
            $hasPrimaryHeading = preg_match('/<h1\b[^>]*>/i', (string) $content) === 1;
        }

        if ($hasPrimaryHeading) {
            $frontend = $this->frontendContext();

            if ($frontend !== null) {
                $frontend->setFrontendData('has_primary_heading', true);
            }
        }

        return $hasPrimaryHeading;
    }

    private function frontendContext(): ?FrontendContextReader
    {
        if (! app()->bound(FrontendContextReader::class)) {
            return null;
        }

        $frontend = resolve(FrontendContextReader::class);

        return $frontend instanceof FrontendContextReader ? $frontend : null;
    }

    private function loadedRelation(Model $model, string $relation): mixed
    {
        if (! $model->relationLoaded($relation)) {
            return null;
        }

        return $model->getRelation($relation);
    }
}
