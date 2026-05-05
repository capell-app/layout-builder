<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Palette;

use Capell\DeveloperTools\Contracts\CommandPaletteProvider;
use Capell\DeveloperTools\Data\CommandPaletteCommandData;
use Capell\DeveloperTools\Enums\CommandPaletteType;
use Capell\DeveloperTools\Filament\Pages\DeveloperToolsPage;
use Capell\DeveloperTools\Filament\Pages\QueueHealthPage;
use Capell\DeveloperTools\Filament\Pages\SystemHealthPage;
use Throwable;

final class DeveloperToolsPaletteCommandProvider implements CommandPaletteProvider
{
    /**
     * @return array<string, CommandPaletteCommandData>
     */
    public function commandPaletteCommands(): array
    {
        $commands = array_filter([
            $this->navigationCommand(
                id: 'developer-tools.open',
                label: 'Open developer tools',
                description: 'Open the Capell developer tools workspace.',
                page: DeveloperToolsPage::class,
                keywords: ['developer', 'tools', 'registry', 'makers'],
                sort: 10,
            ),
            $this->navigationCommand(
                id: 'developer-tools.system-health',
                label: 'Open system health',
                description: 'Review setup, cache, package, registry, and migration health.',
                page: SystemHealthPage::class,
                keywords: ['health', 'cache', 'package', 'registry', 'migration'],
                sort: 11,
            ),
            $this->navigationCommand(
                id: 'developer-tools.queue-health',
                label: 'View failed jobs',
                description: 'Open the queue health report.',
                page: QueueHealthPage::class,
                keywords: ['queue', 'failed', 'jobs'],
                sort: 12,
            ),
        ]);

        return collect($commands)
            ->mapWithKeys(fn (CommandPaletteCommandData $command): array => [$command->id => $command])
            ->all();
    }

    /**
     * @param  class-string  $page
     * @param  array<int, string>  $keywords
     */
    private function navigationCommand(
        string $id,
        string $label,
        string $description,
        string $page,
        array $keywords,
        int $sort,
    ): ?CommandPaletteCommandData {
        try {
            return new CommandPaletteCommandData(
                id: $id,
                label: $label,
                type: CommandPaletteType::Navigation,
                description: $description,
                url: $page::getUrl(panel: 'admin'),
                keywords: [$page, ...$keywords],
                group: 'Developer tools',
                sort: $sort,
            );
        } catch (Throwable) {
            return null;
        }
    }
}
