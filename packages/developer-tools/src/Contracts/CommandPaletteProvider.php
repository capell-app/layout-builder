<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Contracts;

use Capell\DeveloperTools\Data\CommandPaletteCommandData;

interface CommandPaletteProvider
{
    /**
     * @return array<string, CommandPaletteCommandData>
     */
    public function commandPaletteCommands(): array;
}
