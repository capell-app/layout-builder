<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Data\Dashboard;

use Spatie\LaravelData\Data;

final class PackageInfoData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $composerName,
        public readonly string $version,
        public readonly bool $configPublished,
        public readonly string $configPath,
        public readonly ?string $docsUrl,
    ) {}
}
