<?php

declare(strict_types=1);

$rootPath = dirname(__DIR__);
$failures = [
    ...findComposerJsonFailures($rootPath . '/composer.json'),
    ...(shouldCheckComposerLock($rootPath) ? findComposerLockFailures($rootPath . '/composer.lock') : []),
];

if ($failures === []) {
    echo "Composer public files do not contain local path repositories.\n";

    return 0;
}

fwrite(STDERR, "Local Composer path references are not allowed in composer.json or composer.lock.\n");
fwrite(STDERR, "Use composer.local.json and composer.local.lock for local path overlays.\n\n");

foreach ($failures as $failure) {
    fwrite(STDERR, "- {$failure}\n");
}

return 1;

/**
 * @return list<string>
 */
function findComposerJsonFailures(string $composerFile): array
{
    if (! file_exists($composerFile)) {
        return [];
    }

    /** @var array{repositories?: array<int, array<string, mixed>>} $composer */
    $composer = readJsonFile($composerFile);
    $failures = [];

    foreach (($composer['repositories'] ?? []) as $repositoryIndex => $repository) {
        if (($repository['type'] ?? null) === 'path') {
            $failures[] = "composer.json repositories[{$repositoryIndex}] uses type \"path\".";
        }

        $repositoryUrl = $repository['url'] ?? null;

        if (is_string($repositoryUrl) && isLocalPathReference($repositoryUrl)) {
            $failures[] = "composer.json repositories[{$repositoryIndex}] uses local URL \"{$repositoryUrl}\".";
        }
    }

    return $failures;
}

/**
 * @return list<string>
 */
function findComposerLockFailures(string $composerLockFile): array
{
    if (! file_exists($composerLockFile)) {
        return [];
    }

    /**
     * @var array{
     *     packages?: array<int, array<string, mixed>>,
     *     packages-dev?: array<int, array<string, mixed>>
     * } $lock
     */
    $lock = readJsonFile($composerLockFile);
    $failures = [];

    foreach (['packages', 'packages-dev'] as $packageGroup) {
        foreach (($lock[$packageGroup] ?? []) as $packageIndex => $package) {
            $packageName = is_string($package['name'] ?? null) ? $package['name'] : "{$packageGroup}[{$packageIndex}]";

            foreach (['source', 'dist'] as $referenceType) {
                $reference = $package[$referenceType] ?? null;

                if (! is_array($reference)) {
                    continue;
                }

                if (($reference['type'] ?? null) === 'path') {
                    $failures[] = "composer.lock {$packageName} {$referenceType} uses type \"path\".";
                }

                $referenceUrl = $reference['url'] ?? null;

                if (is_string($referenceUrl) && isLocalPathReference($referenceUrl)) {
                    $failures[] = "composer.lock {$packageName} {$referenceType} uses local URL \"{$referenceUrl}\".";
                }
            }
        }
    }

    return $failures;
}

/**
 * @return array<string, mixed>
 */
function readJsonFile(string $file): array
{
    $contents = file_get_contents($file);

    if ($contents === false) {
        throw new RuntimeException("Unable to read {$file}.");
    }

    $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    if (! is_array($decoded)) {
        throw new RuntimeException("Expected {$file} to decode to a JSON object.");
    }

    return $decoded;
}

function isLocalPathReference(string $url): bool
{
    return str_starts_with($url, './')
        || str_starts_with($url, '../')
        || str_starts_with($url, '/')
        || str_starts_with($url, 'file://');
}

function shouldCheckComposerLock(string $rootPath): bool
{
    if (! file_exists($rootPath . '/composer.lock')) {
        return false;
    }

    $trackedCommand = sprintf(
        'git -C %s ls-files --error-unmatch composer.lock >/dev/null 2>&1',
        escapeshellarg($rootPath),
    );

    if (runCommandSucceeds($trackedCommand)) {
        return true;
    }

    $stagedCommand = sprintf(
        'git -C %s diff --cached --name-only -- composer.lock',
        escapeshellarg($rootPath),
    );

    $stagedFiles = shell_exec($stagedCommand);

    return is_string($stagedFiles) && trim($stagedFiles) !== '';
}

function runCommandSucceeds(string $command): bool
{
    exec($command, result_code: $exitCode);

    return $exitCode === 0;
}
