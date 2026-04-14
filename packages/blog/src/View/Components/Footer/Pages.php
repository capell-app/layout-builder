<?php

declare(strict_types=1);

namespace Capell\Blog\View\Components\Footer;

use Capell\Blog\Enums\BlogTypeGroupEnum;
use Capell\Blog\Enums\ModelEnum;
use Capell\Core\Enums\PageOrderEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Loader\PageLoader;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Pages extends Component
{
    public Collection $pages;

    public function __construct(public array $item)
    {
        $this->pages = PageLoader::getPages(
            language: Frontend::language(),
            site: Frontend::site(),
            limit: 3,
            ordering: PageOrderEnum::Latest,
            pageGroup: BlogTypeGroupEnum::Article,
            withImage: true,
            morphModel: CapellCore::getModel(ModelEnum::Article),
        );
    }

    public function render(): ViewContract
    {
        return view('capell-blog::components.footer.pages', [
            ...$this->item,
            'pages' => $this->pages,
        ]);
    }
}
