<?php

declare(strict_types=1);

use Capell\Core\Console\Commands\PublishMigrationsCommand;
use Capell\Core\Models\Theme;
use Capell\Core\Support\Dataset\DatasetPublisher;
use Capell\Core\Support\Migration\MigrationFileManagerInterface;
use Capell\Tests\Fixtures\FakeMigrationFileManager;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\artisan;

afterEach(function (): void {
    Mockery::close();
});

it('runs install command and does not publish files for capell:publish-migrations', function (): void {
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

    $fakeMigrator = Mockery::mock(Migrator::class);
    $fakeDispatcher = Mockery::mock(Dispatcher::class);
    test()->instance(
        MigrateCommand::class,
        Mockery::mock(new MigrateCommand($fakeMigrator, $fakeDispatcher))
            ->makePartial()
            ->shouldReceive('run')->once()->andReturn(0)->getMock(),
    );
    // If Filament AssetsCommand is not available, skip this mock
    if (class_exists('Filament\\Commands\\AssetsCommand')) {
        test()->instance(
            'Filament\\Commands\\AssetsCommand',
            Mockery::mock('Filament\\Commands\\AssetsCommand', [])->makePartial()
                ->shouldReceive('run')->once()->andReturn(0)->getMock(),
        );
    }

    app()->instance(MigrationFileManagerInterface::class, $fakeFileManager);

    $theme = Theme::factory()->create();

    artisan('capell:address-install')
        ->doesntExpectOutput('Publishing migrations')
        ->doesntExpectOutput('Migrating')
        ->doesntExpectOutput('Building assets')
        ->assertExitCode(0);

    // Assert no migration files are published
    expect($fakeFileManager->calls)
        ->not()->toContain(fn (array $call): bool => $call[0] === 'copy')
        ->toBeArray();

    // Assert migrations directory was checked
    expect(collect($fakeFileManager->calls)->contains(
        fn (array $call): bool => $call[0] === 'isDir' && str_contains((string) $call[1], 'database/migrations'),
    ))->toBeTrue();

    // Assert fileExists was not called (no migration file existence check)
    expect(collect($fakeFileManager->calls)->contains(
        fn (array $call): bool => $call[0] === 'fileExists',
    ))->toBeFalse();

    // Assert makeDir was not called (no directory creation)
    expect(collect($fakeFileManager->calls)->contains(
        fn (array $call): bool => $call[0] === 'makeDir',
    ))->toBeFalse();

    // Assert theme exists and has expected attributes
    $themeRow = DB::table('themes')->where('id', $theme->id)->first();
    expect($themeRow)->not()->toBeNull();
});
