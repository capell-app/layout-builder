<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Data;

use Spatie\LaravelData\Data;

class EmailAddressData extends Data
{
    public function __construct(
        public string $email,
        public ?string $name = null,
    ) {}
}
