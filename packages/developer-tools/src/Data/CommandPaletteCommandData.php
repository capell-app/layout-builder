<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Data;

use BackedEnum;
use Capell\DeveloperTools\Enums\CommandPaletteDanger;
use Capell\DeveloperTools\Enums\CommandPaletteType;
use Spatie\LaravelData\Data;

final class CommandPaletteCommandData extends Data
{
    /**
     * @param  array<int, CommandPaletteParameterData>  $parameters
     * @param  array<int, string>  $keywords
     */
    public function __construct(
        public string $id,
        public string $label,
        public CommandPaletteType $type,
        public ?string $description = null,
        public null|string|BackedEnum $icon = null,
        public ?string $url = null,
        public ?string $command = null,
        public ?string $ability = null,
        public CommandPaletteDanger $danger = CommandPaletteDanger::Safe,
        public bool $requiresConfirmation = false,
        public array $parameters = [],
        public array $keywords = [],
        public ?string $group = null,
        public int $sort = 50,
    ) {}
}
