<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\View\Components\Widget\Page;

use Capell\Frontend\Actions\GetPageVariablesAction;
use Capell\Frontend\Facades\Frontend;
use Capell\LayoutBuilder\View\Components\Widget\AbstractWidget;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

abstract class AbstractPagesWidget extends AbstractWidget
{
    public ?string $componentItem = null;

    public Collection $pages;

    protected static string $defaultView = 'capell-layout-builder::components.widget.asset.pages';

    public function render(array $data = []): View|string|Closure
    {
        if ($this->skipRender && config('capell-layout-builder.widget.skip_render_empty', true)) {
            return '';
        }

        $page = Frontend::page();
        $title = $this->widget->translation?->title;
        $content = '';

        if ($title) {
            $content .= '<div class="widget-content">' . e(__($title, GetPageVariablesAction::run($page))) . '</div>';
        }

        if ($this->pages->isEmpty()) {
            $content .= '<div class="no-results">' . e(__('capell-layout-builder::generic.no_pages_found')) . '</div>';
        } else {
            foreach ($this->pages as $pageItem) {
                $itemTitle = $pageItem->translation?->title ?? $pageItem->name;
                $itemUrl = $pageItem->pageUrl?->full_url ?? '#';

                $content .= '<article class="' . e($this->widget->key) . '-page-item">';
                $content .= '<a href="' . e($itemUrl) . '">' . e($itemTitle) . '</a>';
                $content .= '</article>';
            }
        }

        return '<section class="widget widget-' . e($this->widget->key) . ' widget-pages">' . $content . '</section>';
    }
}
