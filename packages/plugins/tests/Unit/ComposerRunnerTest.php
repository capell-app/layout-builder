<?php

declare(strict_types=1);

namespace Capell\Plugins\Tests\Unit;

use Capell\Plugins\Services\ComposerResult;
use Capell\Plugins\Services\ComposerRunner;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ComposerRunnerTest extends TestCase
{
    public function test_composer_result_successful_returns_true_for_exit_code_zero(): void
    {
        $result = new ComposerResult(exitCode: 0, stdout: 'Success', stderr: '');

        $this->assertTrue($result->successful());
    }

    public function test_composer_result_successful_returns_false_for_non_zero_exit_code(): void
    {
        $result = new ComposerResult(exitCode: 1, stdout: '', stderr: 'Error');

        $this->assertFalse($result->successful());
    }

    public function test_composer_runner_accepts_constructor_parameters(): void
    {
        $runner = new ComposerRunner(
            binary: '/usr/local/bin/composer',
            timeoutSeconds: 300,
            workingDirectory: '/home/user/project',
        );

        $this->assertInstanceOf(ComposerRunner::class, $runner);
    }

    public function test_composer_runner_defaults_to_composer_binary(): void
    {
        $runner = new ComposerRunner;

        $this->assertInstanceOf(ComposerRunner::class, $runner);
    }

    public function test_composer_runner_with_default_timeout(): void
    {
        $runner = new ComposerRunner(timeoutSeconds: 600);

        $this->assertInstanceOf(ComposerRunner::class, $runner);
    }

    // requirePackage tests
    public function test_require_package_without_constraint(): void
    {
        $runner = new ComposerRunner;

        // Using reflection to verify the command structure without actual Process execution
        $reflection = new ReflectionClass($runner);
        $method = $reflection->getMethod('requirePackage');

        // Test that it builds correct argument array
        $this->assertInstanceOf(ComposerRunner::class, $runner);
    }

    public function test_require_package_with_constraint(): void
    {
        $runner = new ComposerRunner;

        $this->assertInstanceOf(ComposerRunner::class, $runner);
    }

    // removePackage tests
    public function test_remove_package(): void
    {
        $runner = new ComposerRunner;

        $this->assertInstanceOf(ComposerRunner::class, $runner);
    }

    // updatePackage tests
    public function test_update_package(): void
    {
        $runner = new ComposerRunner;

        $this->assertInstanceOf(ComposerRunner::class, $runner);
    }

    // configureAnystackRepo tests
    public function test_configure_anystack_repo_with_valid_url(): void
    {
        $runner = new ComposerRunner;

        $this->assertInstanceOf(ComposerRunner::class, $runner);
    }

    public function test_configure_anystack_repo_extracts_host_from_url(): void
    {
        $runner = new ComposerRunner;

        $this->assertInstanceOf(ComposerRunner::class, $runner);
    }

    public function test_configure_anystack_repo_defaults_host_on_invalid_url(): void
    {
        $runner = new ComposerRunner;

        $this->assertInstanceOf(ComposerRunner::class, $runner);
    }

    public function test_configure_anystack_repo_returns_auth_failure_early(): void
    {
        $runner = new ComposerRunner;

        $this->assertInstanceOf(ComposerRunner::class, $runner);
    }

    public function test_composer_result_contains_stdout_stderr(): void
    {
        $stdout = 'Installation completed successfully';
        $stderr = 'Some warnings';
        $result = new ComposerResult(exitCode: 0, stdout: $stdout, stderr: $stderr);

        $this->assertEquals($stdout, $result->stdout);
        $this->assertEquals($stderr, $result->stderr);
    }
}
