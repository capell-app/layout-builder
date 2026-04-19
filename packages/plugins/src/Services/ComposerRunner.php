<?php

declare(strict_types=1);

namespace Capell\Plugins\Services;

use Closure;
use Symfony\Component\Process\Process;

final readonly class ComposerRunner
{
    /**
     * @var Closure(array<int, string>, string, int): Process
     */
    private Closure $processFactory;

    /**
     * @param  null|Closure(array<int, string>, string, int): Process  $processFactory
     */
    public function __construct(
        private string $binary = 'composer',
        private int $timeoutSeconds = 600,
        private string $workingDirectory = '',
        ?Closure $processFactory = null,
    ) {
        $this->processFactory = $processFactory ?? static function (array $command, string $workingDir, int $timeout): Process {
            $process = new Process($command, $workingDir);
            $process->setTimeout($timeout);

            return $process;
        };
    }

    public function requirePackage(string $composerName, ?string $constraint = null): ComposerResult
    {
        $args = ['require', '--no-interaction', '--update-with-all-dependencies'];

        $args[] = $constraint !== null ? sprintf('%s:%s', $composerName, $constraint) : $composerName;

        return $this->runCommand($args);
    }

    public function removePackage(string $composerName): ComposerResult
    {
        return $this->runCommand(['remove', '--no-interaction', $composerName]);
    }

    public function updatePackage(string $composerName): ComposerResult
    {
        return $this->runCommand(['update', '--no-interaction', '--with-all-dependencies', $composerName]);
    }

    /**
     * Best-effort removal of the global http-basic auth entry and the local
     * composer repository entry we created in configureAnystackRepo(). Both
     * unsets run independently — if one is missing we still try the other —
     * and the result aggregates their exit codes / stderr so a caller can log
     * a single audit row without losing detail.
     */
    public function removeAnystackRepo(string $productId): ComposerResult
    {
        $host = $productId . '.composer.sh';

        $authResult = $this->runCommand([
            'config',
            '--global',
            '--unset',
            'http-basic.' . $host,
        ]);

        $repoResult = $this->runCommand([
            'config',
            '--unset',
            'repositories.anystack-' . $productId,
        ]);

        $worstExit = max($authResult->exitCode, $repoResult->exitCode);
        $combinedStderr = trim($authResult->stderr . "\n" . $repoResult->stderr);
        $combinedStdout = trim($authResult->stdout . "\n" . $repoResult->stdout);

        return new ComposerResult(
            exitCode: $worstExit,
            stdout: $combinedStdout,
            stderr: $combinedStderr,
        );
    }

    public function configureAnystackRepo(
        string $productId,
        string $licenseKey,
        ?string $fingerprint = null,
    ): ComposerResult {
        $host = $productId . '.composer.sh';
        $contactEmailRaw = config('capell-plugins.anystack.composer_contact_email', 'unlock');
        $user = is_string($contactEmailRaw) && $contactEmailRaw !== '' ? $contactEmailRaw : 'unlock';
        $password = $fingerprint === null ? $licenseKey : sprintf('%s:%s', $licenseKey, $fingerprint);

        $authResult = $this->runCommand([
            'config',
            '--global',
            '--auth',
            'http-basic.' . $host,
            $user,
            $password,
        ]);

        if (! $authResult->successful()) {
            return $authResult;
        }

        $repositoryJson = json_encode([
            'type' => 'composer',
            'url' => 'https://' . $host,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        return $this->runCommand([
            'config',
            'repositories.anystack-' . $productId,
            $repositoryJson,
        ]);
    }

    /**
     * @param  array<int, string>  $args
     */
    private function runCommand(array $args): ComposerResult
    {
        $command = [$this->binary, ...$args];
        $workingDir = $this->workingDirectory !== '' ? $this->workingDirectory : base_path();

        $process = ($this->processFactory)($command, $workingDir, $this->timeoutSeconds);
        $process->run();

        $exitCode = $process->getExitCode();
        if ($exitCode === null) {
            $exitCode = -1;
        }

        return new ComposerResult(
            exitCode: $exitCode,
            stdout: $process->getOutput(),
            stderr: $process->getErrorOutput(),
        );
    }
}
