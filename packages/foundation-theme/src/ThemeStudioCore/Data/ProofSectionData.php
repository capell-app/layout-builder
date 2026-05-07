<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Data;

use Capell\ThemeStudio\Core\Contracts\ThemeSection;
use Spatie\LaravelData\Data;

class ProofSectionData extends Data implements ThemeSection
{
    /**
     * @param  array<int, array{quote?: string, name?: string, role?: string, logo?: string, metric?: string, image?: string, publishedAt?: string, publishedDate?: string, author?: string, type?: string, meta?: array<int, string>}>  $items
     */
    public function __construct(
        public string $heading,
        public ?string $summary = null,
        public array $items = [],
    ) {}

    public function key(): string
    {
        return 'proof';
    }

    public function fallbackKey(): ?string
    {
        return 'content-listing';
    }

    public function toViewData(): array
    {
        return ['section' => $this];
    }
}
