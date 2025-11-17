<?php

declare(strict_types=1);

namespace Capell\Blog\View\Components\Widget\Page;

use Capell\Blog\Enums\ResourceEnum;
use Capell\Blog\Services\Loader\BlogLoader;
use Capell\Core\Models\Page;
use Capell\Frontend\Facades\FrontendLoader;
use Capell\Layout\View\Components\Widget\AbstractWidget;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class Archives extends AbstractWidget
{
    protected ?Page $archivePage = null;

    protected Collection|LengthAwarePaginator $archives;

    protected static string $defaultView = 'capell-layout::components.widget.page.archives';

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
        $type = $this->widget->meta['page_group'] ?? strtolower(ResourceEnum::Article->name);

        $limit = $this->widget->meta['limit'] ?? config('capell-frontend.pagination_limit', 12);

        $this->archives = BlogLoader::getArchives(
            site: FrontendLoader::getSite(),
            language: FrontendLoader::getLanguage(),
            type: $type,
            limit: $limit,
        );

        if ($this->archives->isEmpty() && config('capell-layout.widget.hide_empty')) {
            $this->skipRender = true;

            return;
        }

        $this->archivePage = BlogLoader::getArchivePage(FrontendLoader::getSite(), FrontendLoader::getLanguage());
    }
}
