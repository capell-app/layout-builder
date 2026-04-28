<?php

declare(strict_types=1);

namespace Capell\Plugins\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class PluginSupportData extends Data
{
    public function __construct(
        public readonly ?string $email = null,
        public readonly ?string $docsUrl = null,
        public readonly ?string $supportUrl = null,
    ) {}
}
