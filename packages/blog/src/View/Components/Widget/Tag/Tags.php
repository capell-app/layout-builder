<?php

declare(strict_types=1);

namespace Capell\Blog\View\Components\Widget\Tag;

use Capell\Blog\Services\Loader\TagLoader;
use Capell\Core\Models\Page;
use Capell\Frontend\Facades\Frontend;
use Capell\Layout\View\Components\Widget\AbstractWidget;
use RuntimeException;

class Tags extends AbstractWidget
{
    public ?Page $tagPage = null;

    public $tags;

    protected static string $defaultView = 'capell-blog::components.widget.tag.tags';

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
        $limit = $this->widget->meta['limit'] ?? null;

        $site = Frontend::site();
        $langauge = Frontend::language();

        $this->tags = TagLoader::getTags(
            site: $site,
            language: $langauge,
            limit: $limit,
            hasArticles: true,
        );

        if ($this->tags->isEmpty() && config('capell-layout.widget.skip_render_empty', true)) {
            $this->skipRender = true;

            return;
        }

        $this->tagPage = TagLoader::getTagResultsPage($site, $langauge);

        if (! $this->tagPage) {
            throw new RuntimeException('Tag results page not found for site ID ' . $site->id . ' and language ID ' . $langauge->id);
        }
    }
}
