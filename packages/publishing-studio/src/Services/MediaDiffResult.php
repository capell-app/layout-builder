<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Services;

class MediaDiffResult
{
    public function __construct(
        public readonly ?string $beforeUrl,
        public readonly ?string $afterUrl,
        public readonly ?float $perceptualHashDelta,
        public readonly bool $contentChanged,
    ) {}

    public function hasVisualDiff(): bool
    {
        return $this->beforeUrl !== null || $this->afterUrl !== null;
    }
}
