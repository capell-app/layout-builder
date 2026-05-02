<?php

declare(strict_types=1);

namespace Capell\Mosaic\View\Components\Widget;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Frontend\Facades\Frontend;
use Capell\Mosaic\Actions\HeroWidgetHasPrimaryHeadingAction;
use Capell\Mosaic\Models\Section;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;

class Hero extends AbstractWidget
{
    protected static string $defaultView = 'capell-mosaic::components.widget.hero';

    public static function loadWidgetAssets(array &$morphRelations, ?Language $language = null): void
    {
        $morphRelations[Section::class]['related'] = fn (BuilderContract $query): BuilderContract => $query->with(Section::getMorphRelations($language))
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
