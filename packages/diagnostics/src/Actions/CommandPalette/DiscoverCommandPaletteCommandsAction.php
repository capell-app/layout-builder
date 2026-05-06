<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Actions\CommandPalette;

use Capell\Diagnostics\Contracts\CommandPaletteProvider;
use Capell\Diagnostics\Data\CommandPaletteCommandData;
use Lorisleiva\Actions\Concerns\AsAction;

final class DiscoverCommandPaletteCommandsAction
{
    use AsAction;

    /**
     * @return array<string, CommandPaletteCommandData>
     */
    public function handle(): array
    {
        $commands = [];

        foreach (app()->tagged('capell.diagnostics.command-palette-provider') as $provider) {
            if (! $provider instanceof CommandPaletteProvider) {
                continue;
            }

            foreach ($provider->commandPaletteCommands() as $command) {
                $commands[$command->id] = $command;
            }
        }

        uasort($commands, fn (CommandPaletteCommandData $first, CommandPaletteCommandData $second): int => $first->sort <=> $second->sort);

        return $commands;
    }
}
