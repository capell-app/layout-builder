<?php

declare(strict_types=1);

use Capell\Plugins\Services\ComposerRunner;
use Symfony\Component\Process\Process;

$captured = [];
$nextExitCodes = [];

function makeComposerTestRunner(array $exitCodes = []): ComposerRunner
{
    global $captured, $nextExitCodes;
    $captured = [];
    $nextExitCodes = $exitCodes;

    return new ComposerRunner(
        binary: 'composer',
        timeoutSeconds: 30,
        workingDirectory: sys_get_temp_dir(),
        processFactory: function (array $command, string $cwd, int $timeout): Process {
            global $captured, $nextExitCodes;
            $captured[] = ['command' => $command, 'cwd' => $cwd, 'timeout' => $timeout];
            $exitCode = array_shift($nextExitCodes) ?? 0;

            return Process::fromShellCommandline('true');
        },
    );
}

test('require package without constraint', function () use (&$captured): void {
    $runner = makeComposerTestRunner();

    $result = $runner->requirePackage('vendor/name');

    expect($result->successful())->toBeTrue();
});

test('require package with constraint', function (): void {
    $runner = makeComposerTestRunner();
    $result = $runner->requirePackage('vendor/name', '^1.2');

    expect($result->successful())->toBeTrue();
});

test('remove and update package build correct args', function (): void {
    $runner = makeComposerTestRunner();

    $removeResult = $runner->removePackage('vendor/name');
    $updateResult = $runner->updatePackage('vendor/name');

    expect($removeResult->successful())->toBeTrue();
    expect($updateResult->successful())->toBeTrue();
});
