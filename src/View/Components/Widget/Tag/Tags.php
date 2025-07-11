<?php

declare(strict_types=1);

namespace Capell\Layout\View\Components\Widget\Tag;

use Capell\Core\Models\Page;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Services\Loader\TagLoader;
use Capell\Layout\View\Components\Widget\AbstractWidget;

class Tags extends AbstractWidget
{
    public ?Page $tagPage = null;

    public $tags;

    protected static string $defaultView = 'capell-layout::components.widget.tag.tags';

    public function render(array $data = [])
    {
        return parent::render([
            ...$data,
            'tagPage' => $this->tagPage,
            'tags' => $this->tags,
        ]);
    }

    protected function mountWidget(): void
    {
        $limit = $this->widget->meta['limit'] ?? config('capell-frontend.pagination_limit', 12);

        $this->tags = TagLoader::getTags(
            site: Frontend::getSite(),
            language: Frontend::getLanguage(),
            limit: $limit
        );

        if ($this->tags->isEmpty() && $this->containerKey !== 'main') {
            $this->skipRender = true;

            return;
        }

        $this->tagPage = TagLoader::getTagResultsPage(Frontend::getSite(), Frontend::getLanguage());
    }
}
