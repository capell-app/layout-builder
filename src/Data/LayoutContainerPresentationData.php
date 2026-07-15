<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

final class LayoutContainerPresentationData extends Data
{
    /**
     * @param  list<string>  $margin
     */
    public function __construct(
        public readonly ?string $spacing,
        public readonly LayoutContainerResponsivePaddingData $padding,
        public readonly ?string $border,
        public readonly array $margin,
        public readonly ?LayoutContainerThemePresentationData $theme = null,
    ) {}

    /**
     * @return list<string>
     */
    public function classes(): array
    {
        return array_values(array_filter([
            $this->spacing === null ? null : 'capell-container-spacing-' . $this->spacing,
            $this->border === null ? null : 'capell-container-border-' . $this->border,
            ...$this->padding->classes(),
            ...array_map(
                static fn (string $value): string => 'capell-container-margin-' . str_replace('_', '-', $value),
                $this->margin,
            ),
            ...($this->theme?->classes() ?? []),
        ], static fn (?string $class): bool => is_string($class) && $class !== ''));
    }
}
