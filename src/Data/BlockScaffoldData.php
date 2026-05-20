<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

class BlockScaffoldData extends Data
{
    public function __construct(
        public readonly string $viewPath,
        public readonly bool $created,
        public readonly string $seederSnippet,
    ) {}
}
