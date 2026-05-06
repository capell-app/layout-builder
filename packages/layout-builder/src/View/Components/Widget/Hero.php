<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\View\Components\Widget;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Frontend\Facades\Frontend;
use Capell\LayoutBuilder\Actions\HeroWidgetHasPrimaryHeadingAction;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Model;

class Hero extends AbstractWidget
{
    protected static string $defaultView = 'capell-layout-builder::components.widget.hero';

    public static function loadWidgetAssets(array &$morphRelations, ?Language $language = null): void
    {
        $morphRelations[Page::class]['related'] = fn (BuilderContract $query): BuilderContract => $query->with(Page::getMorphRelations($language))
            ->withWhereHas('translation', fn (BuilderContract $query): BuilderContract => $query->with('language'));

        foreach (array_keys($morphRelations) as $assetModel) {
            if ($assetModel === Page::class || ! is_a($assetModel, Model::class, true)) {
                continue;
            }

            if (! method_exists($assetModel, 'getMorphRelations')) {
                continue;
            }

            $morphRelations[$assetModel]['related'] = fn (BuilderContract $query): BuilderContract => $query
                ->with($assetModel::getMorphRelations($language))
                ->withWhereHas('translation', fn (BuilderContract $query): BuilderContract => $query->with('language'));
        }
    }

    protected function mountWidget(): void
    {
        $page = Frontend::page();

        $hasHero = isset($page->translation->meta['hero']) && filled($page->translation->meta['hero']);

        if (
            $hasHero === false &&
            blank($this->widget->translation?->content) &&
            $this->widget->assets->isEmpty()
        ) {
            $this->skipRender = true;

            return;
        }

        HeroWidgetHasPrimaryHeadingAction::run($this->widget, $page);
    }
}
