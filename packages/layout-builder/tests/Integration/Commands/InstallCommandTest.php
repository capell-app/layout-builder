<?php

declare(strict_types=1);

use Capell\Core\Console\Commands\PublishMigrationsCommand;
use Capell\Core\Support\Dataset\DatasetPublisher;
use Capell\Core\Support\Migration\MigrationFilesystemInterface;
use Capell\Tests\Fixtures\FakeMigrationFileManager;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Migrations\MigrationAssistant;

use function Pest\Laravel\artisan;

afterEach(function (): void {
    Mockery::close();
});

it('runs layout-builder install command successfully without publishing files', function (): void {
    $fakeFileManager = new FakeMigrationFileManager([
        'fileExists' => [],
        'isDir' => [],
    ]);

    $fakeDatasetPublisher = Mockery::mock(DatasetPublisher::class);
    test()->instance(
        PublishMigrationsCommand::class,
        Mockery::mock(new PublishMigrationsCommand($fakeDatasetPublisher, $fakeFileManager))
            ->makePartial()
            ->shouldReceive('run')->once()->andReturn(0)->getMock(),
    );

    $fakeMigrationAssistant = Mockery::mock(MigrationAssistant::class);
    $fakeDispatcher = Mockery::mock(Dispatcher::class);
    test()->instance(
        MigrateCommand::class,
        Mockery::mock(new MigrateCommand($fakeMigrationAssistant, $fakeDispatcher))
            ->makePartial()
            ->shouldReceive('run')->once()->andReturn(0)->getMock(),
    );

    if (class_exists('Filament\\Commands\\AssetsCommand')) {
        test()->instance(
            'Filament\\Commands\\AssetsCommand',
            Mockery::mock('Filament\\Commands\\AssetsCommand', [])->makePartial()
                ->shouldReceive('run')->once()->andReturn(0)->getMock(),
        );
    }

    app()->instance(MigrationFilesystemInterface::class, $fakeFileManager);

    artisan('capell:layout-builder-install')
        ->doesntExpectOutput('Publishing migrations')
        ->doesntExpectOutput('Migrating')
        ->doesntExpectOutput('Building assets')
        ->expectsOutput('Capell LayoutBuilder installed successfully.')
        ->assertExitCode(Command::SUCCESS);

    expect($fakeFileManager->calls)
        ->not()->toContain(fn (array $call): bool => $call[0] === 'copy')
        ->toBeArray()
        ->and(collect($fakeFileManager->calls)->contains(
            fn (array $call): bool => $call[0] === 'isDir',
        ))->toBeTrue();

});
