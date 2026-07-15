<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

final class LayoutContainerResponsivePaddingData extends Data
{
    /**
     * @param  list<string>  $base
     * @param  list<string>|null  $tablet
     * @param  list<string>|null  $desktop
     */
    public function __construct(
        public readonly array $base,
        public readonly ?array $tablet,
        public readonly ?array $desktop,
    ) {}

    /**
     * @return list<string>
     */
    public function classes(): array
    {
        return [
            ...$this->classesFor($this->base, 'capell-container-padding'),
            ...$this->classesFor($this->tablet, 'capell-container-padding-tablet'),
            ...$this->classesFor($this->desktop, 'capell-container-padding-desktop'),
        ];
    }

    /**
     * @param  list<string>|null  $values
     * @return list<string>
     */
    private function classesFor(?array $values, string $prefix): array
    {
        if ($values === null) {
            return [];
        }

        return array_map(
            static fn (string $value): string => $prefix . '-' . str_replace('_', '-', $value),
            $values,
        );
    }
}
