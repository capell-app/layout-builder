<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Actions\CommandPalette;

use Capell\DeveloperTools\Contracts\CommandPaletteProvider;
use Capell\DeveloperTools\Data\CommandPaletteCommandData;
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

        foreach (app()->tagged('capell.developer-tools.command-palette-provider') as $provider) {
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
