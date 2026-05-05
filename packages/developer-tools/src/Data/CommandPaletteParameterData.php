<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Data;

use Capell\DeveloperTools\Enums\CommandPaletteParameterType;
use Spatie\LaravelData\Data;

final class CommandPaletteParameterData extends Data
{
    /**
     * @param  array<int, string>  $rules
     */
    public function __construct(
        public string $name,
        public string $label,
        public CommandPaletteParameterType $type,
        public bool $required = false,
        public ?string $description = null,
        public mixed $default = null,
        public array $rules = [],
    ) {}
}
