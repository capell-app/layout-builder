<?php

declare(strict_types=1);

namespace Capell\Blog\View\Components\Footer;

use Capell\Blog\Support\Loader\TagLoader;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Page;
use Capell\Frontend\Facades\Frontend;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Tags extends Component
{
    public ?Page $tagPage = null;

    public Collection $tags;

    public function __construct(public array $item)
    {
        $language = Frontend::language();
        $site = Frontend::site();

        $this->tags = TagLoader::getTags($site, $language, limit: 5, hasArticles: true);

        if ($this->tags->isEmpty()) {
            return;
        }

        $tagPage = TagLoader::getTagResultsPage($site, $language);
        if (! $tagPage instanceof Pageable) {
            return;
        }

        $this->tagPage = $tagPage;
    }

    public function render(): ?ViewContract
    {
        if (! $this->tagPage instanceof Pageable || $this->tags->isEmpty()) {
            return null;
        }

        return view('capell-blog::components.footer.tags', [
            ...$this->item,
            'tagPage' => $this->tagPage,
            'tags' => $this->tags,
            'language' => Frontend::language(),
        ]);
    }
}
