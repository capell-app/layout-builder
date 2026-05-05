<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Palette;

use Capell\DeveloperTools\Contracts\CommandPaletteProvider;
use Capell\DeveloperTools\Data\CommandPaletteCommandData;
use Capell\DeveloperTools\Data\CommandPaletteParameterData;
use Capell\DeveloperTools\Enums\CommandPaletteDanger;
use Capell\DeveloperTools\Enums\CommandPaletteParameterType;
use Capell\DeveloperTools\Enums\CommandPaletteType;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

final class CapellArtisanPaletteCommandProvider implements CommandPaletteProvider
{
    /**
     * @return array<string, CommandPaletteCommandData>
     */
    public function commandPaletteCommands(): array
    {
        $commands = [];

        foreach (Artisan::all() as $name => $consoleCommand) {
            if (! str_starts_with((string) $name, 'capell:')) {
                continue;
            }

            $command = new CommandPaletteCommandData(
                id: 'artisan.' . $name,
                label: Str::headline(str_replace(['capell:', '-'], ['', ' '], $name)),
                type: CommandPaletteType::Artisan,
                description: $consoleCommand->getDescription() !== '' ? $consoleCommand->getDescription() : null,
                command: $name,
                ability: 'palette.run.' . str_replace([':', '-'], '_', $name),
                danger: $this->dangerForCommand($name),
                requiresConfirmation: $this->dangerForCommand($name) !== CommandPaletteDanger::Safe,
                parameters: $this->parametersForCommand($consoleCommand),
                keywords: [$name],
                group: 'Developer tools',
                sort: 80,
            );

            $commands[$command->id] = $command;
        }

        return $commands;
    }

    private function dangerForCommand(string $name): CommandPaletteDanger
    {
        if (Str::contains($name, ['demo', 'install', 'setup', 'upgrade'])) {
            return CommandPaletteDanger::Dangerous;
        }

        if (Str::contains($name, ['clear', 'cache', 'publish'])) {
            return CommandPaletteDanger::Confirm;
        }

        return CommandPaletteDanger::Safe;
    }

    /**
     * @return array<int, CommandPaletteParameterData>
     */
    private function parametersForCommand(Command $command): array
    {
        $parameters = [];
        $definition = $command->getDefinition();

        foreach ($definition->getArguments() as $argument) {
            $parameters[] = new CommandPaletteParameterData(
                name: $argument->getName(),
                label: Str::headline($argument->getName()),
                type: CommandPaletteParameterType::String,
                required: $argument->isRequired(),
                description: $argument->getDescription() !== '' ? $argument->getDescription() : null,
                default: $argument->getDefault(),
            );
        }

        foreach ($definition->getOptions() as $option) {
            if ($this->isGlobalOption($option)) {
                continue;
            }

            $parameters[] = new CommandPaletteParameterData(
                name: '--' . $option->getName(),
                label: Str::headline($option->getName()),
                type: $option->acceptValue() ? CommandPaletteParameterType::String : CommandPaletteParameterType::Boolean,
                required: $option->isValueRequired(),
                description: $option->getDescription() !== '' ? $option->getDescription() : null,
                default: $this->defaultForOption($option),
            );
        }

        return $parameters;
    }

    private function defaultForOption(InputOption $option): mixed
    {
        if (! $option->acceptValue()) {
            return false;
        }

        return $option->getDefault();
    }

    private function isGlobalOption(InputOption $option): bool
    {
        return in_array($option->getName(), [
            'help',
            'quiet',
            'verbose',
            'version',
            'ansi',
            'no-ansi',
            'no-interaction',
            'env',
        ], true);
    }
}
