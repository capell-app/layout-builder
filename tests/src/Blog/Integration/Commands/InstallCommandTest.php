<?php

declare(strict_types=1);

use Capell\Core\Console\Commands\PublishMigrationsCommand;
use Capell\Core\Support\Dataset\DatasetPublisher;
use Capell\Core\Support\Migration\MigrationFileManagerInterface;
use Capell\Tests\Fixtures\FakeMigrationFileManager;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Migrations\Migrator;

use function Pest\Laravel\artisan;

afterEach(function (): void {
    Mockery::close();
});

it('runs blog install command successfully without publishing files', function (): void {
    $fakeFileManager = new FakeMigrationFileManager([
        'fileExists' => [],
        'isDir' => [],
    ]);

    $fakeDatasetPublisher = Mockery::mock(DatasetPublisher::class);

    // Ensure calls to publish migrations are no-ops and counted (called twice in Blog install)
    test()->instance(
        PublishMigrationsCommand::class,
        Mockery::mock(new PublishMigrationsCommand($fakeDatasetPublisher, $fakeFileManager))
            ->makePartial()
            ->shouldReceive('run')->once()->andReturn(0)->getMock(),
    );

    // Ensure migrate command is a no-op
    $fakeMigrator = Mockery::mock(Migrator::class);
    $fakeDispatcher = Mockery::mock(Dispatcher::class);
    test()->instance(
        MigrateCommand::class,
        Mockery::mock(new MigrateCommand($fakeMigrator, $fakeDispatcher))
            ->makePartial()
            ->shouldReceive('run')->once()->andReturn(0)->getMock(),
    );

    // If Filament AssetsCommand is available, stub it as a no-op
    if (class_exists('Filament\\Commands\\AssetsCommand')) {
        test()->instance(
            'Filament\\Commands\\AssetsCommand',
            Mockery::mock('Filament\\Commands\\AssetsCommand', [])->makePartial()
                ->shouldReceive('run')->once()->andReturn(0)->getMock(),
        );
    }

    app()->instance(MigrationFileManagerInterface::class, $fakeFileManager);

    artisan('capell:blog-install')
        ->doesntExpectOutput('Publishing migrations')
        ->doesntExpectOutput('Migrating')
        ->doesntExpectOutput('Building assets')
        ->expectsOutput('Capell Blog installed successfully.')
        ->assertExitCode(Command::SUCCESS);

    // Assert no migration files were actually published
    expect($fakeFileManager->calls)
        ->not()->toContain(fn (array $call): bool => $call[0] === 'copy')
        ->toBeArray();

    // Assert no directory/file operations were attempted by the publish command internals
    expect(collect($fakeFileManager->calls)->contains(
        fn (array $call): bool => in_array($call[0], ['fileExists', 'isDir', 'makeDir'], true),
    ))->toBeFalse();
});
