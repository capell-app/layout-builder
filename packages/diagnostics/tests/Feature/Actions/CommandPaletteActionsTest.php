<?php

declare(strict_types=1);

use Capell\Diagnostics\Actions\CommandPalette\DiscoverCommandPaletteCommandsAction;
use Capell\Diagnostics\Actions\CommandPalette\ExecuteCommandPaletteCommandAction;
use Capell\Diagnostics\Actions\CommandPalette\ValidateCommandPaletteParametersAction;
use Capell\Diagnostics\Contracts\CommandPaletteProvider;
use Capell\Diagnostics\Data\CommandPaletteCommandData;
use Capell\Diagnostics\Data\CommandPaletteParameterData;
use Capell\Diagnostics\Enums\CommandPaletteParameterType;
use Capell\Diagnostics\Enums\CommandPaletteType;
use Capell\Diagnostics\Models\CommandPaletteRun;
use Capell\Diagnostics\Palette\CapellArtisanPaletteCommandProvider;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Console\Command\Command;

it('discovers command palette commands from tagged providers in sort order', function (): void {
    app()->instance(TestCommandPaletteProvider::class, new TestCommandPaletteProvider);
    app()->tag([TestCommandPaletteProvider::class], 'capell.diagnostics.command-palette-provider');

    $commands = DiscoverCommandPaletteCommandsAction::run();

    expect(array_keys($commands))->toContain('test.navigate')
        ->and(array_keys($commands))->toContain('test.artisan')
        ->and(array_search('test.navigate', array_keys($commands), true))
        ->toBeLessThan(array_search('test.artisan', array_keys($commands), true));
});

it('validates command palette parameters using command parameter metadata', function (): void {
    $command = testPaletteArtisanCommand();

    $validated = ValidateCommandPaletteParametersAction::run($command, [
        'name' => 'Ben',
        '--loud' => true,
    ]);

    expect($validated)->toBe([
        'name' => 'Ben',
        '--loud' => true,
    ]);

    ValidateCommandPaletteParametersAction::run($command, [
        '--loud' => true,
    ]);
})->throws(ValidationException::class);

it('executes navigation commands and records successful command palette runs', function (): void {
    app()->instance(TestCommandPaletteProvider::class, new TestCommandPaletteProvider);
    app()->tag([TestCommandPaletteProvider::class], 'capell.diagnostics.command-palette-provider');

    $user = $this->createUser();

    $result = ExecuteCommandPaletteCommandAction::run('test.navigate', [], $user);

    $run = CommandPaletteRun::query()->firstOrFail();

    expect($result->successful)->toBeTrue()
        ->and($result->url)->toBe('/admin/system-health')
        ->and($result->runId)->toBe($run->getKey())
        ->and($run->status)->toBe('succeeded')
        ->and($run->command_id)->toBe('test.navigate');
});

it('executes artisan commands with validated parameters and stores command output', function (): void {
    Artisan::command('capell:test-output {name} {--loud}', function (): int {
        // @phpstan-ignore variable.undefined
        $message = 'Hello ' . $this->argument('name');

        if ($this->option('loud') === true) {
            $message = strtoupper($message);
        }

        $this->line($message);

        return Command::SUCCESS;
    });
    app()->instance(TestCommandPaletteProvider::class, new TestCommandPaletteProvider);
    app()->tag([TestCommandPaletteProvider::class], 'capell.diagnostics.command-palette-provider');

    $user = $this->createUser();

    $result = ExecuteCommandPaletteCommandAction::run('test.artisan', [
        'name' => 'Ben',
        '--loud' => true,
    ], $user);

    $run = CommandPaletteRun::query()->latest('id')->firstOrFail();

    expect($result->successful)->toBeTrue()
        ->and($result->title)->toBe('Test output completed')
        ->and($result->body)->toContain('HELLO BEN')
        ->and($run->output)->toContain('HELLO BEN')
        ->and($run->exit_code)->toBe(0);
});

it('requires confirmation before executing commands marked for confirmation', function (): void {
    app()->instance(TestCommandPaletteProvider::class, new TestCommandPaletteProvider);
    app()->tag([TestCommandPaletteProvider::class], 'capell.diagnostics.command-palette-provider');

    ExecuteCommandPaletteCommandAction::run('test.confirmed', [], $this->createUser());
})->throws(AuthorizationException::class);

it('exposes capell artisan commands as palette commands with parameter metadata', function (): void {
    Artisan::command('capell:test-provider {name} {--force}', fn (): int => Command::SUCCESS)->describe('Run the test provider command.');

    $commands = (new CapellArtisanPaletteCommandProvider)->commandPaletteCommands();
    $command = $commands['artisan.capell:test-provider'];

    expect($command->label)->toBe('Test Provider')
        ->and($command->description)->toBe('Run the test provider command.')
        ->and($command->command)->toBe('capell:test-provider')
        ->and($command->parameters)->toHaveCount(2)
        ->and($command->parameters[0]->name)->toBe('name')
        ->and($command->parameters[0]->required)->toBeTrue()
        ->and($command->parameters[1]->name)->toBe('--force')
        ->and($command->parameters[1]->type)->toBe(CommandPaletteParameterType::Boolean);
});

function testPaletteArtisanCommand(): CommandPaletteCommandData
{
    return new CommandPaletteCommandData(
        id: 'test.artisan',
        label: 'Test output',
        type: CommandPaletteType::Artisan,
        command: 'capell:test-output',
        parameters: [
            new CommandPaletteParameterData(
                name: 'name',
                label: 'Name',
                type: CommandPaletteParameterType::String,
                required: true,
            ),
            new CommandPaletteParameterData(
                name: '--loud',
                label: 'Loud',
                type: CommandPaletteParameterType::Boolean,
            ),
        ],
        sort: 20,
    );
}

final class TestCommandPaletteProvider implements CommandPaletteProvider
{
    /**
     * @return array<string, CommandPaletteCommandData>
     */
    public function commandPaletteCommands(): array
    {
        return [
            'test.artisan' => testPaletteArtisanCommand(),
            'test.navigate' => new CommandPaletteCommandData(
                id: 'test.navigate',
                label: 'Open system health',
                type: CommandPaletteType::Navigation,
                url: '/admin/system-health',
                sort: 10,
            ),
            'test.confirmed' => new CommandPaletteCommandData(
                id: 'test.confirmed',
                label: 'Confirmed command',
                type: CommandPaletteType::Navigation,
                url: '/admin/system-health',
                requiresConfirmation: true,
                sort: 30,
            ),
        ];
    }
}
