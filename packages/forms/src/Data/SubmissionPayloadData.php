<?php

declare(strict_types=1);

namespace Capell\Forms\Data;

use Spatie\LaravelData\Data;

class SubmissionPayloadData extends Data
{
    /**
     * @param  array<string, mixed>  $values
     */
    public function __construct(
        public array $values = [],
    ) {}
}
