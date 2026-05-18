<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Capell\LayoutBuilder\Enums\LayoutDiagnosticSeverity;
use Spatie\LaravelData\Data;

final class LayoutDiagnosticData extends Data
{
    public function __construct(
        public LayoutDiagnosticSeverity $severity,
        public string $code,
        public string $message,
        public ?string $containerKey,
        public ?int $blockIndex,
    ) {}

    public function isBlocking(): bool
    {
        return $this->severity === LayoutDiagnosticSeverity::Blocking;
    }
}
