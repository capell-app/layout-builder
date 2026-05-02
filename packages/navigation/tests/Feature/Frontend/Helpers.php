<?php

declare(strict_types=1);

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Query log for test assertions.
 *
 * @var array<int, array<string, mixed>>
 */
static $testQueryLog = [];

function setupQueryLogging(): void
{
    global $testQueryLog;
    $testQueryLog = [];

    DB::listen(function (QueryExecuted $query) use (&$testQueryLog): void {
        $trace = collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 100))
            ->map(fn (array $frame): ?string => isset($frame['file']) ? $frame['file'] . ':' . ($frame['line'] ?? '?') : null)
            ->filter();
        $testQueryLog[] = [
            'query' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time,
            'trace' => $trace,
        ];
    });
}

function getTestQueryLog(): array
{
    global $testQueryLog;

    return $testQueryLog;
}

function clearTestQueryLog(): void
{
    global $testQueryLog;
    $testQueryLog = [];
}

/**
 * @param  array{query:string, bindings:array<int, mixed>, time:float, trace:Collection<int, string>}  $queryEntry
 */
function buildQuerySignature(array $queryEntry): string
{
    return $queryEntry['query'] . '|' . serialize($queryEntry['bindings']);
}
