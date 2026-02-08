<?php

declare(strict_types=1);

namespace Capell\Blog\View\Components\Widget\Page;

use Capell\Blog\Enums\BlogTypeGroupEnum;
use Capell\Blog\Support\Loader\BlogLoader;
use Capell\Core\Models\Page;
use Capell\Frontend\Facades\Frontend;
use Capell\Layout\View\Components\Widget\AbstractWidget;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class Archives extends AbstractWidget
{
    protected ?Page $archivePage = null;

    protected Collection|LengthAwarePaginator $archives;

    protected static string $defaultView = 'capell-blog::components.widget.page.archives';

    public function render(array $data = [])
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

        throw_unless($this->archivePage, Exception::class, 'Blog Archives Widget: No archive page not found');

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

        $this->skipRender = ! empty($this->widgetData['meta']['hide_no_results']) || config('capell-layout.widget.skip_render_empty') === true;
    }
}
