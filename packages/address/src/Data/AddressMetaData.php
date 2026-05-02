<?php

declare(strict_types=1);

namespace Capell\Address\Data;

use Spatie\LaravelData\Data;

class AddressMetaData extends Data
{
    public function __construct(
        public ?string $latitude,
        public ?string $longitude,
    ) {}
}
