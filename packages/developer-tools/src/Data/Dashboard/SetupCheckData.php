<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Data\Dashboard;

use Capell\Admin\Enums\SetupHealthEnum;
use Spatie\LaravelData\Data;

final class SetupCheckData extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly SetupHealthEnum $status,
        public readonly ?string $fixUrl = null,
        public readonly ?string $fixLabel = null,
    ) {}
}
