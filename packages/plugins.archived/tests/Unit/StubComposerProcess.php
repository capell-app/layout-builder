<?php

declare(strict_types=1);

namespace Capell\Plugins\Tests\Unit;

use Symfony\Component\Process\Process;

/**
 * Helper that builds a real Symfony Process producing a deterministic exit code,
 * stdout, and stderr — without subclassing Process (whose run() is @final).
 */
final class StubComposerProcess
{
    public static function make(
        int $exitCode = 0,
        string $output = '',
        string $errorOutput = '',
    ): Process {
        // Use /bin/sh to echo stdout, emit stderr, then exit with the given code.
        $escapedOut = escapeshellarg($output);
        $escapedErr = escapeshellarg($errorOutput);
        $script = sprintf('printf %%s %s; printf %%s %s 1>&2; exit %d', $escapedOut, $escapedErr, $exitCode);

        return Process::fromShellCommandline($script);
    }
}
