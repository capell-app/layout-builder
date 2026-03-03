<?php

declare(strict_types=1);

namespace Capell\Blog\View\Components\Widget\Page;

use Capell\Core\Models\Page;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Loader\PageLoader;
use Capell\Layout\View\Components\Widget\AbstractWidget;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;

class Article extends AbstractWidget
{
    public ?Authenticatable $author = null;

    public ?Page $nextPage = null;

    public ?Page $previousPage = null;

    protected static string $defaultView = 'capell-blog::components.widget.page.article';

    public function render(array $data = []): View|string|Closure
    {
        return parent::render([
            ...$data,
            'previousPage' => $this->previousPage,
            'nextPage' => $this->nextPage,
        ]);
    }

    protected function mountWidget(): void
    {
        $page = Frontend::page();
        $language = Frontend::language();
        $site = Frontend::site();

        if (! isset($page->type->meta['hidden']) && isset($this->widget->meta['with_next_prev'])) {
            $this->previousPage = PageLoader::getPreviousPage($page, $site, $language);
            $this->nextPage = PageLoader::getNextPage($page, $site, $language);
        }

        if (isset($this->widget->meta['with_author'])) {
            $this->author = PageLoader::getPageAuthor($page);
        }
    }
}
