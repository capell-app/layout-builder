<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Filament\Pages;

use BackedEnum;
use Capell\DeveloperTools\Actions\CommandPalette\DiscoverCommandPaletteCommandsAction;
use Capell\DeveloperTools\Actions\CommandPalette\ExecuteCommandPaletteCommandAction;
use Capell\DeveloperTools\Data\CommandPaletteCommandData;
use Capell\DeveloperTools\Data\CommandPaletteParameterData;
use Capell\DeveloperTools\Enums\CommandPaletteDanger;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Override;

final class CommandPalettePage extends Page
{
    public string $query = '';

    public ?string $selectedCommandId = null;

    /**
     * @var array<string, mixed>
     */
    public array $parameters = [];

    public bool $confirmed = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCommandLine;

    protected static ?string $slug = 'developer-tools/command-palette';

    protected static ?int $navigationSort = 10;

    protected string $view = 'capell-developer-tools::filament.pages.command-palette';

    #[Override]
    public static function getNavigationLabel(): string
    {
        return 'Command Palette';
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return __('capell-admin::navigation.group_administration');
    }

    public static function canAccess(): bool
    {
        if (Gate::allows('accessDeveloperTools')) {
            return true;
        }

        if (Gate::allows('viewDeveloperTools')) {
            return true;
        }

        if (auth()->user()?->can('accessDeveloperTools') === true) {
            return true;
        }

        return auth()->user()?->can('viewDeveloperTools') === true;
    }

    public function getTitle(): string
    {
        return 'Command Palette';
    }

    /**
     * @return array<string, array<int, CommandPaletteCommandData>>
     */
    #[Computed]
    public function groupedCommands(): array
    {
        return collect($this->visibleCommands())
            ->filter(fn (CommandPaletteCommandData $command): bool => $this->matchesQuery($command))
            ->groupBy(fn (CommandPaletteCommandData $command): string => $command->group ?? 'Commands')
            ->map(fn ($commands) => $commands->values()->all())
            ->all();
    }

    #[Computed]
    public function selectedCommand(): ?CommandPaletteCommandData
    {
        if ($this->selectedCommandId === null) {
            return null;
        }

        return collect($this->visibleCommands())
            ->first(fn (CommandPaletteCommandData $command): bool => $command->id === $this->selectedCommandId);
    }

    public function selectCommand(string $commandId): void
    {
        $command = $this->authorizedCommand($commandId);

        $this->selectedCommandId = $command->id;
        $this->confirmed = false;
        $this->parameters = collect($command->parameters)
            ->mapWithKeys(fn (CommandPaletteParameterData $parameter): array => [$parameter->name => $parameter->default])
            ->all();
    }

    public function clearSelection(): void
    {
        $this->selectedCommandId = null;
        $this->confirmed = false;
        $this->parameters = [];
    }

    public function executeSelectedCommand(): void
    {
        if ($this->selectedCommandId === null) {
            return;
        }

        $result = ExecuteCommandPaletteCommandAction::run(
            $this->selectedCommandId,
            [...$this->parameters, '_confirmed' => $this->confirmed],
            auth()->user() ?? throw new AuthorizationException,
        );

        $notification = Notification::make()
            ->title($result->title)
            ->body($result->body);

        if ($result->successful) {
            $notification->success();
        } else {
            $notification->danger();
        }

        $notification->send();

        if ($result->url !== null) {
            $this->redirect($result->url, navigate: true);

            return;
        }

        $this->clearSelection();
    }

    public function warningFor(CommandPaletteCommandData $command): ?string
    {
        return match ($command->danger) {
            CommandPaletteDanger::Safe => null,
            CommandPaletteDanger::Confirm => 'This operational command requires confirmation before it runs.',
            CommandPaletteDanger::Dangerous => 'This command may make broad or destructive operational changes.',
        };
    }

    /**
     * @return array<string, CommandPaletteCommandData>
     */
    private function visibleCommands(): array
    {
        return array_filter(
            DiscoverCommandPaletteCommandsAction::run(),
            $this->canSeeCommand(...),
        );
    }

    private function canSeeCommand(CommandPaletteCommandData $command): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        if ($command->ability === null) {
            return true;
        }

        return $user->can($command->ability);
    }

    private function authorizedCommand(string $commandId): CommandPaletteCommandData
    {
        $command = collect($this->visibleCommands())
            ->first(fn (CommandPaletteCommandData $paletteCommand): bool => $paletteCommand->id === $commandId);

        throw_unless($command instanceof CommandPaletteCommandData, AuthorizationException::class);

        return $command;
    }

    private function matchesQuery(CommandPaletteCommandData $command): bool
    {
        if ($this->query === '') {
            return true;
        }

        $query = mb_strtolower($this->query);

        return str_contains(mb_strtolower($command->label), $query)
            || ($command->description !== null && str_contains(mb_strtolower($command->description), $query))
            || collect($command->keywords)->contains(fn (string $keyword): bool => str_contains(mb_strtolower($keyword), $query));
    }
}
