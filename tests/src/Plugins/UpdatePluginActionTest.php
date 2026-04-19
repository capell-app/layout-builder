<?php

declare(strict_types=1);

namespace Capell\Tests\Plugins;

use Capell\Plugins\Actions\UpdatePluginAction;
use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Plugins\Services\ComposerRunner;
use Capell\Tests\Plugins\Unit\StubComposerProcess;
use RuntimeException;
use Symfony\Component\Process\Process;

final class UpdatePluginActionTest extends PluginsTestCase
{
    /**
     * @var array<int, array<int, string>>
     */
    private array $captured = [];

    /**
     * @var array<int, int>
     */
    private array $nextExitCodes = [];

    /**
     * @var array<int, string>
     */
    private array $nextErrorOutputs = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->captured = [];
        $this->nextExitCodes = [];
        $this->nextErrorOutputs = [];
    }

    public function test_happy_path_updates_package_and_audits(): void
    {
        $plugin = MarketplacePlugin::factory()->create([
            'composer_name' => 'vendor/some-plugin',
        ]);

        $action = new UpdatePluginAction($this->makeRunner([0]));
        $action->handle($plugin);

        $this->assertCount(1, $this->captured);
        $this->assertSame(
            ['composer', 'update', '--no-interaction', '--with-all-dependencies', 'vendor/some-plugin'],
            $this->captured[0],
        );

        $this->assertTrue($plugin->auditLog()->where('action', 'updated')->exists());
    }

    public function test_composer_failure_throws_and_logs_audit(): void
    {
        $plugin = MarketplacePlugin::factory()->create([
            'composer_name' => 'vendor/some-plugin',
        ]);

        $action = new UpdatePluginAction($this->makeRunner([2], ['Dependency conflict']));

        try {
            $action->handle($plugin);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $runtimeException) {
            $this->assertStringContainsString('exit code 2', $runtimeException->getMessage());
        }

        $auditEntry = $plugin->auditLog()->where('action', 'update_failed')->first();
        $this->assertNotNull($auditEntry);
        $data = $auditEntry->data->getArrayCopy();
        $this->assertSame(2, $data['exit_code']);
        $this->assertStringContainsString('Dependency conflict', (string) $data['stderr_tail']);
    }

    /**
     * @param  array<int, int>  $exitCodes
     * @param  array<int, string>  $errorOutputs
     */
    private function makeRunner(array $exitCodes, array $errorOutputs = []): ComposerRunner
    {
        $this->nextExitCodes = $exitCodes;
        $this->nextErrorOutputs = $errorOutputs;

        return new ComposerRunner(
            binary: 'composer',
            timeoutSeconds: 30,
            workingDirectory: sys_get_temp_dir(),
            processFactory: function (array $command, string $cwd, int $timeout): Process {
                $this->captured[] = $command;
                $exitCode = array_shift($this->nextExitCodes) ?? 0;
                $errorOutput = array_shift($this->nextErrorOutputs) ?? '';

                return StubComposerProcess::make($exitCode, '', $errorOutput);
            },
        );
    }
}
