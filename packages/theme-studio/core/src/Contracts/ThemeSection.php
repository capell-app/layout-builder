<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Contracts;

interface ThemeSection
{
    public function key(): string;

    public function fallbackKey(): ?string;

    /**
     * @return array<string, mixed>
     */
    public function toViewData(): array;
}
