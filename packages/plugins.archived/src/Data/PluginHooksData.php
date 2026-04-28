<?php

declare(strict_types=1);

namespace Capell\Plugins\Data;

use Spatie\LaravelData\Data;

final class PluginHooksData extends Data
{
    public function __construct(
        public readonly ?string $install = null,
        public readonly ?string $uninstall = null,
        public readonly ?string $upgrade = null,
    ) {}
}
