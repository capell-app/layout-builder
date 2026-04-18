<?php

declare(strict_types=1);

namespace Capell\Plugins\Services;

use Symfony\Component\Process\Process;

final class ComposerRunner
{
    public function __construct(
        private readonly string $binary = 'composer',
        private readonly int $timeoutSeconds = 600,
        private readonly string $workingDirectory = '',
    ) {}

    public function requirePackage(string $composerName, ?string $constraint = null): ComposerResult
    {
        $args = ['require', '--no-interaction', '--update-with-all-dependencies'];

        if ($constraint !== null) {
            $args[] = "{$composerName}:{$constraint}";
        } else {
            $args[] = $composerName;
        }

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

    public function configureAnystackRepo(string $repoUrl, string $vendor, string $licenseKey): ComposerResult
    {
        // Extract host from the repo URL
        $host = parse_url($repoUrl, PHP_URL_HOST);

        if ($host === null || $host === false) {
            $host = 'repo.anystack.sh';
        }

        // First command: set global auth
        $authResult = $this->runCommand([
            'config',
            '--global',
            '--auth',
            "http-basic.{$host}",
            $vendor,
            $licenseKey,
        ]);

        if (! $authResult->successful()) {
            return $authResult;
        }

        // Second command: add repository configuration
        $repositoryJson = json_encode([
            'type' => 'composer',
            'url' => $repoUrl,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        return $this->runCommand([
            'config',
            'repositories.' . $vendor . '-anystack',
            $repositoryJson,
        ]);
    }

    private function runCommand(array $args): ComposerResult
    {
        $command = [$this->binary, ...$args];
        $workingDir = $this->workingDirectory !== '' ? $this->workingDirectory : base_path();

        $process = new Process($command, $workingDir);
        $process->setTimeout($this->timeoutSeconds)->run();

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
