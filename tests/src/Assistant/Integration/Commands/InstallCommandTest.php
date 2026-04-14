<?php

declare(strict_types=1);

use Capell\Core\Support\Migration\MigrationFileManagerInterface;
use Capell\Tests\Fixtures\FakeMigrationFileManager;
use Illuminate\Console\Command;

use function Pest\Laravel\artisan;

afterEach(function (): void {
    Mockery::close();
});

it('runs assistant install command successfully', function (): void {
    $fakeFileManager = new FakeMigrationFileManager([
        'fileExists' => [],
        'isDir' => [],
    ]);
    app()->instance(MigrationFileManagerInterface::class, $fakeFileManager);

    artisan('capell:assistant-install')
        ->expectsOutput('Capell Assistant installed successfully.')
        ->assertExitCode(Command::SUCCESS);

    expect(collect($fakeFileManager->calls)->contains(
        fn (array $call): bool => $call[0] === 'isDir',
    ))->toBeTrue();

    expect(collect($fakeFileManager->calls)->contains(
        fn (array $call): bool => $call[0] === 'copy',
    ))->toBeFalse();
});
