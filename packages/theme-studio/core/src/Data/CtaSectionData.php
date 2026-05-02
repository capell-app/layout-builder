<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Data;

use Capell\ThemeStudio\Core\Contracts\ThemeSection;
use Spatie\LaravelData\Data;

class CtaSectionData extends Data implements ThemeSection
{
    /**
     * @param  array<int, array{label: string, url: string, style?: string}>  $actions
     */
    public function __construct(
        public string $heading,
        public ?string $summary = null,
        public array $actions = [],
    ) {}

    public function key(): string
    {
        return 'cta';
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
