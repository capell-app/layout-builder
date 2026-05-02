<?php

declare(strict_types=1);

namespace Capell\Blog\View\Components;

use Closure;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;

class AssetAfterTitle extends Component
{
    public function __construct(
        public ?DateTimeImmutable $publishDate = null,
        public ?string $publishDatePosition = null,
        public ?Collection $tags = null,
        public ?Closure $publishDateOutput = null,
    ) {}

    public function render(): ?View
    {
        if (
            (! $this->publishDate instanceof DateTimeImmutable || $this->publishDatePosition !== 'bottom')
            && ! $this->tags?->isNotEmpty()
        ) {
            return null;
        }

        return view('capell-blog::hooks.asset-after-title', [
            'publishDate' => $this->publishDate,
            'publishDatePosition' => $this->publishDatePosition,
            'tags' => $this->tags,
            'publishDateOutput' => $this->publishDateOutput,
        ]);
    }
}
