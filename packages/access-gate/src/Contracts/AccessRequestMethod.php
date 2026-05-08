<?php

declare(strict_types=1);

namespace Capell\AccessGate\Contracts;

use Capell\AccessGate\Models\Area;

interface AccessRequestMethod
{
    public function key(): string;

    public function label(): string;

    public function description(): ?string;

    public function isEnabled(Area $area): bool;

    public function isPrimary(Area $area): bool;

    public function url(Area $area, ?string $requestedUrl = null): string;
}
