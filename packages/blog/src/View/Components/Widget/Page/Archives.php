<?php

declare(strict_types=1);

namespace Capell\Blog\View\Components\Widget\Page;

use Capell\Blog\Enums\ResourceEnum;
use Capell\Blog\Services\Loader\BlogLoader;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Frontend\Facades\Frontend;
use Capell\Layout\View\Components\Widget\AbstractWidget;
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

        if (! $this->archivePage) {
            CapellCore::log(
                'Blog Archives Widget: No archive page not found',
                ['site_id' => $site->id, 'language' => $language->code],
            );
            $this->skipRender = true;
        }

        $type = $this->widget->meta['page_group'] ?? strtolower(ResourceEnum::Article->name);

        $limit = $this->widget->meta['limit'] ?? config('capell-frontend.pagination_limit', 12);

        $this->archives = BlogLoader::getArchives(
            site: $site,
            language: $language,
            type: $type,
            limit: $limit,
        );

        if ($this->archives->isEmpty() && config('capell-layout.widget.skip_render_empty', true)) {
            $this->skipRender = true;

            return;
        }
    }
}
