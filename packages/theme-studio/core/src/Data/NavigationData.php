<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Data;

use Capell\ThemeStudio\Core\Contracts\ThemeSection;
use Spatie\LaravelData\Data;

class NavigationData extends Data implements ThemeSection
{
    /**
     * @param  array<int, array{label: string, url: string}>  $items
     */
    public function __construct(
        public string $brandName,
        public array $items = [],
        public ?string $ctaLabel = null,
        public ?string $ctaUrl = null,
    ) {}

    public function key(): string
    {
        return 'navigation';
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
