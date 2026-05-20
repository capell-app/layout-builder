<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Layout;
use Spatie\LaravelData\Data;

final class LayoutContentInventoryContextData extends Data
{
    public function __construct(
        public Layout $layout,
        public ?Pageable $page,
        public ?string $siteName,
        public ?string $languageName,
    ) {}
}
