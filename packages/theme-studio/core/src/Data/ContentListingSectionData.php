<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Data;

use Capell\ThemeStudio\Core\Contracts\ThemeSection;
use Spatie\LaravelData\Data;

class ContentListingSectionData extends Data implements ThemeSection
{
    /**
     * @param  array<int, array{title: string, summary?: string, url?: string, image?: string}>  $items
     */
    public function __construct(
        public string $heading,
        public ?string $summary = null,
        public array $items = [],
    ) {}

    public function key(): string
    {
        return 'content-listing';
    }

    public function fallbackKey(): ?string
    {
        return null;
    }

    public function toViewData(): array
    {
        return ['section' => $this];
    }
}
