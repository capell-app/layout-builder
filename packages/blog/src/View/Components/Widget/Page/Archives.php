<?php

declare(strict_types=1);

namespace Capell\Blog\View\Components\Widget\Page;

use Capell\Blog\Enums\BlogTypeGroupEnum;
use Capell\Blog\Support\Loader\BlogLoader;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Page;
use Capell\Frontend\Facades\Frontend;
use Capell\LayoutBuilder\View\Components\Widget\AbstractWidget;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class Archives extends AbstractWidget
{
    protected ?Page $archivePage = null;

    protected null|Collection|LengthAwarePaginator $archives = null;

    protected static string $defaultView = 'capell-blog::components.widget.page.archives';

    public function render(array $data = []): View|string|Closure
    {
        return parent::render([
            ...$data,
            'archivePage' => $this->archivePage,
            'archives' => $this->archives,
        ]);
    }

    protected function mountWidget(): void
    {
        $language = Frontend::language();
        $site = Frontend::site();

        $this->archivePage = BlogLoader::getArchivePage($site, $language);

        if (! $this->archivePage instanceof Pageable) {
            $this->skipRender = true;

            return;
        }

        $group = $this->widget->meta['page_group'] ?? BlogTypeGroupEnum::Article->value;

        $limit = $this->widget->meta['limit'] ?? config('capell-frontend.pagination_limit', 12);

        $this->archives = BlogLoader::getArchives(
            site: $site,
            language: $language,
            group: $group,
            limit: $limit,
        );

        if ($this->archives->isNotEmpty()) {
            return;
        }

        if (isset($this->widgetData['meta']['hide_no_results']) && $this->widgetData['meta']['hide_no_results']) {
            $this->skipRender = true;
        }

        if (config('capell-layout-builder.widget.skip_render_empty') === true) {
            $this->skipRender = true;
        }
    }
}
