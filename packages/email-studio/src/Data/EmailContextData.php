<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Data;

use Spatie\LaravelData\Data;

class EmailContextData extends Data
{
    /**
     * @param  array<string, mixed>  $variables
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public array $variables = [],
        public bool $preview = false,
        public array $metadata = [],
    ) {}
}
