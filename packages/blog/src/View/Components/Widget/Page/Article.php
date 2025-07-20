<?php

declare(strict_types=1);

namespace Capell\Blog\View\Components\Widget\Page;

use App\Models\User;
use Capell\Core\Models\Page;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Services\Loader\PageLoader;
use Capell\Frontend\Services\Loader\TagLoader;
use Capell\Layout\View\Components\Widget\AbstractWidget;

class Article extends AbstractWidget
{
    // @phpstan-ignore-next-line
    public ?User $author = null;

    public ?Page $nextPage = null;

    public ?Page $previousPage = null;

    public ?Page $tagPage = null;

    public $tags = [];

    protected static string $defaultView = 'capell-blog::components.widget.page.article';

    public function render(array $data = [])
    {
        return parent::render([
            ...$data,
            'tagPage' => $this->tagPage,
            'tags' => $this->tags,
            'previousPage' => $this->previousPage,
            'nextPage' => $this->nextPage,
            'author' => $this->author,
        ]);
    }

    protected function mountWidget(): void
    {
        if (empty(Frontend::getPage()->type->meta['hidden']) && ! empty($this->widget->meta['with_next_prev'])) {
            $this->previousPage = PageLoader::getPreviousPage(Frontend::getPage(), Frontend::getSite(), Frontend::getLanguage());
            $this->nextPage = PageLoader::getNextPage(Frontend::getPage(), Frontend::getSite(), Frontend::getLanguage());
        }

        if (! empty($this->widget->meta['with_author']) && ! empty(Frontend::getPage()->meta['author_id'])) {
            $this->author = PageLoader::getPageAuthor(Frontend::getPage());
        }

        if (! empty($this->widget->meta['with_tags'])) {
            $this->tags = TagLoader::getPageTags(Frontend::getPage());

            if ($this->tags->isNotEmpty()) {
                $this->tagPage = TagLoader::getTagResultsPage(Frontend::getSite(), Frontend::getLanguage());
            }
        }
    }
}
