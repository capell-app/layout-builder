<?php

declare(strict_types=1);

namespace Capell\Mosaic\View\Components\Widget\Page;

use Capell\Core\Enums\PageOrderEnum;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Loader\PageLoader;
use Capell\Frontend\Support\Logging\FrontendLogger;

class Children extends AbstractPagesWidget
{
    protected static string $defaultView = 'capell-mosaic::components.widget.asset.pages';

    protected function mountWidget(): void
    {
        $page = Frontend::page();

        if (! $page->hasPageHierarchy()) {
            $logger = resolve(FrontendLogger::class);

            $logger->warning('Frontend: page has no page hierarchy for children widget', [
                'pageable_type' => $page->getMorphClass(),
                'pageable_id' => $page->getKey(),
                'layout_id' => $page->layout->key,
            ]);

            $this->skipRender = true;

            return;
        }

        if (isset($page->type->meta['hidden']) && $page->type->meta['hidden'] === true) {
            $this->skipRender = true;

            return;
        }

        $this->pages = PageLoader::getPages(
            language: Frontend::language(),
            site: Frontend::site(),
            page: Frontend::page(),
            type: 'children',
            ordering: PageOrderEnum::Alphabetical,
            withChildrenCount: $this->widget->meta['with_children_count'] ?? false,
            withImage: $this->widget->meta['with_image'] ?? false,
            withParent: $this->widget->meta['with_parent'] ?? false,
            withDate: $this->widget->meta['with_date'] ?? false,
            useCache: false,
        );

        if ($this->pages->isEmpty()) {
            $this->skipRender = true;
        }
    }
}
