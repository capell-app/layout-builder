<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Contracts;

use Capell\Diagnostics\Data\CommandPaletteCommandData;

interface CommandPaletteProvider
{
    /**
     * @return array<string, CommandPaletteCommandData>
     */
    public function commandPaletteCommands(): array;
}
