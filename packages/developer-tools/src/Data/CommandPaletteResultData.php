<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Data;

use Spatie\LaravelData\Data;

final class CommandPaletteResultData extends Data
{
    public function __construct(
        public bool $successful,
        public string $title,
        public ?string $body = null,
        public ?string $url = null,
        public ?int $runId = null,
    ) {}
}
