<?php

declare(strict_types=1);

namespace Capell\Blog\View\Components\Page;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;

class BeforeContentTags extends Component
{
    public function __construct(public ?Model $item, public Collection $tags) {}

    public function render(): ?View
    {
        if (! $this->tags?->isNotEmpty()) {
            return null;
        }

        return view('capell-blog::page.tags', [
            'item' => $this->item,
            'tags' => $this->tags,
        ]);
    }
}
