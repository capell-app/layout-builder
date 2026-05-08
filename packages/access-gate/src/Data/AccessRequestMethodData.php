<?php

declare(strict_types=1);

namespace Capell\AccessGate\Data;

final class AccessRequestMethodData
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $url,
        public readonly bool $primary = false,
        public readonly ?string $description = null,
    ) {}
}
