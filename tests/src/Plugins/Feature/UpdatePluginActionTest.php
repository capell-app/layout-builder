<?php

declare(strict_types=1);

use Capell\Plugins\Actions\UpdatePluginAction;
use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Plugins\Services\ComposerRunner;
use Capell\Tests\Plugins\Unit\StubComposerProcess;
use Symfony\Component\Process\Process;

function makeUpdateRunner(array $exitCodes, array $errorOutputs = []): ComposerRunner
{
    $captured = [];

    return new ComposerRunner(
        binary: 'composer',
        timeoutSeconds: 30,
        workingDirectory: sys_get_temp_dir(),
        processFactory: function (array $command, string $cwd, int $timeout) use (&$captured, &$exitCodes, &$errorOutputs): Process {
            $captured[] = $command;
            $exitCode = array_shift($exitCodes) ?? 0;
            $errorOutput = array_shift($errorOutputs) ?? '';

            return StubComposerProcess::make($exitCode, '', $errorOutput);
        },
    );
}

test('happy path updates package and audits', function (): void {
    $plugin = MarketplacePlugin::factory()->create([
        'composer_name' => 'vendor/some-plugin',
    ]);

    $action = new UpdatePluginAction(makeUpdateRunner([0]));
    $action->handle($plugin);

    expect($plugin->auditLog()->where('action', 'updated')->exists())->toBeTrue();
});

test('composer failure throws and logs audit', function (): void {
    $plugin = MarketplacePlugin::factory()->create([
        'composer_name' => 'vendor/some-plugin',
    ]);

    $action = new UpdatePluginAction(makeUpdateRunner([2], ['Dependency conflict']));

    expect(fn () => $action->handle($plugin))
        ->toThrow(RuntimeException::class, 'exit code 2');

    $auditEntry = $plugin->auditLog()->where('action', 'update_failed')->first();
    expect($auditEntry)->not()->toBeNull();
    $data = $auditEntry->data->getArrayCopy();
    expect($data['exit_code'])->toBe(2);
    expect((string) $data['stderr_tail'])->toContain('Dependency conflict');
});
