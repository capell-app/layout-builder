<?php

declare(strict_types=1);

namespace Capell\Tests\Plugins\Unit;

use Capell\Plugins\Services\ComposerRunner;
use Capell\Tests\Plugins\PluginsTestCase;
use Symfony\Component\Process\Process;

final class ComposerRunnerTest extends PluginsTestCase
{
    /**
     * @var array<int, array{command: array<int, string>, cwd: string, timeout: int}>
     */
    private array $captured = [];

    /**
     * @var array<int, int>
     */
    private array $nextExitCodes = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->captured = [];
        $this->nextExitCodes = [];
    }

    public function test_require_package_without_constraint(): void
    {
        $runner = $this->makeRunner();

        $result = $runner->requirePackage('vendor/name');

        $this->assertTrue($result->successful());
        $this->assertCount(1, $this->captured);
        $this->assertEquals(
            ['composer', 'require', '--no-interaction', '--update-with-all-dependencies', 'vendor/name'],
            $this->captured[0]['command'],
        );
    }

    public function test_require_package_with_constraint(): void
    {
        $runner = $this->makeRunner();

        $runner->requirePackage('vendor/name', '^1.2');

        $this->assertEquals(
            ['composer', 'require', '--no-interaction', '--update-with-all-dependencies', 'vendor/name:^1.2'],
            $this->captured[0]['command'],
        );
    }

    public function test_remove_and_update_package_build_correct_args(): void
    {
        $runner = $this->makeRunner();

        $runner->removePackage('vendor/name');
        $runner->updatePackage('vendor/name');

        $this->assertEquals(
            ['composer', 'remove', '--no-interaction', 'vendor/name'],
            $this->captured[0]['command'],
        );

        $this->assertEquals(
            ['composer', 'update', '--no-interaction', '--with-all-dependencies', 'vendor/name'],
            $this->captured[1]['command'],
        );
    }

    public function test_configure_anystack_repo_runs_two_commands_with_default_user(): void
    {
        config()->set('capell-plugins.anystack.composer_contact_email', 'unlock');

        $runner = $this->makeRunner();

        $result = $runner->configureAnystackRepo('my-prod', 'abc');

        $this->assertTrue($result->successful());
        $this->assertCount(2, $this->captured);

        $authCommand = $this->captured[0]['command'];
        $this->assertEquals('composer', $authCommand[0]);
        $this->assertEquals('config', $authCommand[1]);
        $this->assertEquals('--global', $authCommand[2]);
        $this->assertEquals('--auth', $authCommand[3]);
        $this->assertEquals('http-basic.my-prod.composer.sh', $authCommand[4]);
        $this->assertEquals('unlock', $authCommand[5]);
        $this->assertEquals('abc', $authCommand[6]);

        $repoCommand = $this->captured[1]['command'];
        $this->assertEquals('composer', $repoCommand[0]);
        $this->assertEquals('config', $repoCommand[1]);
        $this->assertEquals('repositories.anystack-my-prod', $repoCommand[2]);

        $repoJson = json_decode($repoCommand[3], true);
        $this->assertIsArray($repoJson);
        $this->assertEquals('composer', $repoJson['type']);
        $this->assertEquals('https://my-prod.composer.sh', $repoJson['url']);
    }

    public function test_configure_anystack_repo_uses_configured_contact_email(): void
    {
        config()->set('capell-plugins.anystack.composer_contact_email', 'me@example.com');

        $runner = $this->makeRunner();

        $runner->configureAnystackRepo('my-prod', 'abc');

        $this->assertEquals('me@example.com', $this->captured[0]['command'][5]);
    }

    public function test_configure_anystack_repo_appends_fingerprint_to_password(): void
    {
        config()->set('capell-plugins.anystack.composer_contact_email', 'unlock');

        $runner = $this->makeRunner();

        $runner->configureAnystackRepo('my-prod', 'abc', 'fp123');

        $this->assertEquals('abc:fp123', $this->captured[0]['command'][6]);
    }

    public function test_auth_failure_short_circuits_repo_command(): void
    {
        $runner = $this->makeRunner([1]);

        $result = $runner->configureAnystackRepo('my-prod', 'abc');

        $this->assertFalse($result->successful());
        $this->assertSame(1, $result->exitCode);
        $this->assertCount(1, $this->captured, 'second command must not be issued when auth fails');
    }

    /**
     * @param  array<int, int>  $exitCodes
     */
    private function makeRunner(array $exitCodes = []): ComposerRunner
    {
        $this->nextExitCodes = $exitCodes;

        return new ComposerRunner(
            binary: 'composer',
            timeoutSeconds: 30,
            workingDirectory: sys_get_temp_dir(),
            processFactory: function (array $command, string $cwd, int $timeout): Process {
                $exitCode = array_shift($this->nextExitCodes) ?? 0;
                $this->captured[] = [
                    'command' => $command,
                    'cwd' => $cwd,
                    'timeout' => $timeout,
                ];

                return StubComposerProcess::make($exitCode);
            },
        );
    }
}
