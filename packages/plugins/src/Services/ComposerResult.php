<?php

declare(strict_types=1);

namespace Capell\Plugins\Services;

final class ComposerResult
{
    public function __construct(
        public readonly int $exitCode,
        public readonly string $stdout,
        public readonly string $stderr,
    ) {}

    public function successful(): bool
    {
        return $this->exitCode === 0;
    }
}
