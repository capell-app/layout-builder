<?php

declare(strict_types=1);

use Capell\Plugins\Actions\UninstallPluginAction;
use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Plugins\Services\ComposerRunner;
use Capell\Plugins\Tests\Unit\StubComposerProcess;
use Symfony\Component\Process\Process;

function makeUninstallRunner(array $exitCodes, array $errorOutputs = []): ComposerRunner
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

test('happy path removes package and audits', function (): void {
    $plugin = MarketplacePlugin::factory()->create([
        'composer_name' => 'vendor/some-plugin',
    ]);

    $action = new UninstallPluginAction(makeUninstallRunner([0]));
    $action->handle($plugin);

    expect($plugin->auditLog()->where('action', 'uninstalled')->exists())->toBeTrue();
});

test('composer failure throws and logs stderr tail', function (): void {
    $plugin = MarketplacePlugin::factory()->create([
        'composer_name' => 'vendor/some-plugin',
    ]);

    $action = new UninstallPluginAction(makeUninstallRunner([1], ['Package not found']));

    expect(fn () => $action->handle($plugin))
        ->toThrow(RuntimeException::class);

    $auditEntry = $plugin->auditLog()->where('action', 'uninstall_failed')->first();
    expect($auditEntry)->not()->toBeNull();
    $data = $auditEntry->data->getArrayCopy();
    expect($data['exit_code'])->toBe(1);
    expect((string) $data['stderr_tail'])->toContain('Package not found');
});
