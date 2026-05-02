<?php

declare(strict_types=1);

namespace Capell\Blog\Data\Dashboard;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

final class TopPagesData extends Data
{
    /**
     * @param  Collection<int, TopPageData>  $pages
     */
    public function __construct(
        public readonly Collection $pages,
    ) {}
}
