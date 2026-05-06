<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Actions\CommandPalette;

use Capell\Diagnostics\Data\CommandPaletteCommandData;
use Capell\Diagnostics\Data\CommandPaletteResultData;
use Capell\Diagnostics\Enums\CommandPaletteType;
use Capell\Diagnostics\Models\CommandPaletteRun;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use RuntimeException;
use Throwable;

final class ExecuteCommandPaletteCommandAction
{
    use AsAction;

    private ?int $lastExitCode = null;

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function handle(string $commandId, array $parameters, Authenticatable $user): CommandPaletteResultData
    {
        $command = $this->authorizedCommand($commandId, $user);
        $this->authorizeConfirmation($command, $parameters);

        $validatedParameters = ValidateCommandPaletteParametersAction::run($command, $parameters);
        $run = $this->startRun($command, $validatedParameters, $user);

        try {
            $result = $this->execute($command, $validatedParameters);

            $run->update([
                'status' => $result->successful ? 'succeeded' : 'failed',
                'output' => $result->body,
                'exit_code' => $this->lastExitCode,
                'finished_at' => now(),
            ]);

            return new CommandPaletteResultData(
                successful: $result->successful,
                title: $result->title,
                body: $result->body,
                url: $result->url,
                runId: $run->id,
            );
        } catch (Throwable $throwable) {
            $run->update([
                'status' => 'failed',
                'output' => Str::limit($throwable->getMessage(), 4000, ''),
                'finished_at' => now(),
            ]);

            throw $throwable;
        }
    }

    private function authorizedCommand(string $commandId, Authenticatable $user): CommandPaletteCommandData
    {
        $command = collect(DiscoverCommandPaletteCommandsAction::run())
            ->first(fn (CommandPaletteCommandData $paletteCommand): bool => $paletteCommand->id === $commandId);

        throw_unless($command instanceof CommandPaletteCommandData, AuthorizationException::class);

        throw_if($command->ability !== null && Gate::forUser($user)->denies($command->ability), AuthorizationException::class);

        return $command;
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function authorizeConfirmation(CommandPaletteCommandData $command, array $parameters): void
    {
        if (! $command->requiresConfirmation) {
            return;
        }

        throw_if(($parameters['_confirmed'] ?? false) !== true, AuthorizationException::class, 'This command requires confirmation before it can run.');
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function startRun(CommandPaletteCommandData $command, array $parameters, Authenticatable $user): CommandPaletteRun
    {
        return CommandPaletteRun::query()->create([
            'user_id' => $user->getAuthIdentifier(),
            'command_id' => $command->id,
            'command_label' => $command->label,
            'ability' => $command->ability,
            'status' => 'running',
            'parameters' => $parameters,
            'started_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function execute(CommandPaletteCommandData $command, array $parameters): CommandPaletteResultData
    {
        return match ($command->type) {
            CommandPaletteType::Navigation => $this->navigate($command),
            CommandPaletteType::Artisan => $this->runArtisanCommand($command, $parameters),
        };
    }

    private function navigate(CommandPaletteCommandData $command): CommandPaletteResultData
    {
        if ($command->url === null) {
            throw new RuntimeException(sprintf('Command [%s] does not define a URL.', $command->id));
        }

        return new CommandPaletteResultData(
            successful: true,
            title: $command->label,
            url: $command->url,
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function runArtisanCommand(CommandPaletteCommandData $command, array $parameters): CommandPaletteResultData
    {
        if ($command->command === null) {
            throw new RuntimeException(sprintf('Command [%s] does not define an Artisan command.', $command->id));
        }

        $this->lastExitCode = Artisan::call($command->command, $parameters);
        $output = Str::limit(Artisan::output(), 4000, '');

        return new CommandPaletteResultData(
            successful: $this->lastExitCode === 0,
            title: $this->lastExitCode === 0 ? $command->label . ' completed' : $command->label . ' failed',
            body: $output,
        );
    }
}
