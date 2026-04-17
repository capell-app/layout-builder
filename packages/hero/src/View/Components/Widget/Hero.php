<?php

declare(strict_types=1);

namespace Capell\Hero\View\Components\Widget;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Frontend\Facades\Frontend;
use Capell\Hero\Actions\HeroWidgetHasPrimaryHeadingAction;
use Capell\Mosaic\Models\Content;
use Capell\Mosaic\View\Components\Widget\AbstractWidget;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;

class Hero extends AbstractWidget
{
    protected static string $defaultView = 'capell-hero::components.widget.hero';

    public static function loadWidgetAssets(array &$morphRelations, ?Language $language = null): void
    {
        $morphRelations[Content::class]['related'] = fn (BuilderContract $query): BuilderContract => $query->with(Content::getMorphRelations($language))
            ->withWhereHas('translation', fn (BuilderContract $query): BuilderContract => $query->with('language'));

        $morphRelations[Page::class]['related'] = fn (BuilderContract $query): BuilderContract => $query->with(Page::getMorphRelations($language))
            ->withWhereHas('translation', fn (BuilderContract $query): BuilderContract => $query->with('language'));
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
