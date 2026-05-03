<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\Frontend\Http\Middleware\HtmlCacheMiddleware;
use Capell\Frontend\Support\Logging\FrontendLogger;
use Symfony\Component\Finder\Finder;

it('does not allow unsafe PHP, debug, or forbidden global functions', function (): void {
    $ignoredFiles = ignoredSourceFiles([
        FrontendLogger::class,
        EditPage::class,
        HtmlCacheMiddleware::class,
    ]);

    $violations = forbiddenGlobalFunctionCalls([
        'debug_zval_dump',
        'debug_backtrace',
        'debug_print_backtrace',
        'md5',
        'sha1',
        'uniqid',
        'rand',
        'mt_rand',
        'tempnam',
        'str_shuffle',
        'shuffle',
        'array_rand',
        'eval',
        'exec',
        'shell_exec',
        'system',
        'passthru',
        'create_function',
        'unserialize',
        'extract',
        'mb_parse_str',
        'dl',
        'assert',
        'dump',
        'ray',
        'rd',
        'ds',
        'die',
        'goto',
        'global',
        'var_dump',
        'phpinfo',
        'echo',
        'ereg',
        'eregi',
        'mysql_connect',
        'mysql_pconnect',
        'mysql_query',
        'mysql_select_db',
        'mysql_fetch_array',
        'mysql_fetch_assoc',
        'mysql_fetch_object',
        'mysql_fetch_row',
        'mysql_num_rows',
        'mysql_affected_rows',
        'mysql_free_result',
        'mysql_insert_id',
        'mysql_error',
        'mysql_real_escape_string',
        'print',
        'print_r',
        'var_dump',
        'xdebug_break',
        'xdebug_call_class',
        'xdebug_call_file',
        'xdebug_call_int',
        'xdebug_call_line',
        'xdebug_code_coverage_started',
        'xdebug_connect_to_client',
        'xdebug_debug_zval',
        'xdebug_debug_zval_stdout',
        'xdebug_dump_superglobals',
        'xdebug_get_code_coverage',
        'xdebug_get_collected_errors',
        'xdebug_get_function_count',
        'xdebug_get_function_stack',
        'xdebug_get_gc_run_count',
        'xdebug_get_gc_total_collected_roots',
        'xdebug_get_gcstats_filename',
        'xdebug_get_headers',
        'xdebug_get_monitored_functions',
        'xdebug_get_profiler_filename',
        'xdebug_get_stack_depth',
        'xdebug_get_tracefile_name',
        'xdebug_info',
        'xdebug_is_debugger_active',
        'xdebug_memory_usage',
        'xdebug_notify',
        'xdebug_peak_memory_usage',
        'xdebug_print_function_stack',
        'xdebug_set_filter',
        'xdebug_start_code_coverage',
        'xdebug_start_error_collection',
        'xdebug_start_function_monitor',
        'xdebug_start_gcstats',
        'xdebug_start_trace',
        'xdebug_stop_code_coverage',
        'xdebug_stop_error_collection',
        'xdebug_stop_function_monitor',
        'xdebug_stop_gcstats',
        'xdebug_stop_trace',
        'xdebug_time_index',
        'xdebug_var_dump',
        'trap',
        'dd',
        'exit',
        'env',
        'sleep',
        'usleep',
    ], $ignoredFiles);

    expect($violations)->toBe(
        [],
        'Forbidden global function calls found:' . PHP_EOL .
        json_encode($violations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});

/**
 * @param  list<class-string>  $classes
 * @return list<string>
 */
function ignoredSourceFiles(array $classes): array
{
    $files = [];

    foreach ($classes as $class) {
        $reflection = new ReflectionClass($class);
        $file = $reflection->getFileName();

        if (is_string($file)) {
            $files[] = $file;
        }
    }

    return $files;
}

/**
 * @param  list<string>  $functionNames
 * @param  list<string>  $ignoredFiles
 * @return array<string, list<string>>
 */
function forbiddenGlobalFunctionCalls(array $functionNames, array $ignoredFiles): array
{
    $forbiddenFunctions = array_fill_keys($functionNames, true);
    $ignoredLookup = array_fill_keys($ignoredFiles, true);

    $files = (new Finder)
        ->in(__DIR__ . '/../../../packages')
        ->path('/src/')
        ->name('*.php')
        ->files();

    $violations = [];

    foreach ($files as $file) {
        $path = $file->getRealPath();

        if (! is_string($path) || isset($ignoredLookup[$path])) {
            continue;
        }

        $tokens = token_get_all($file->getContents());
        $tokenCount = count($tokens);

        foreach ($tokens as $index => $token) {
            if (! is_array($token)) {
                continue;
            }

            if ($token[0] !== T_STRING) {
                continue;
            }

            $functionName = strtolower($token[1]);

            if (! isset($forbiddenFunctions[$functionName])) {
                continue;
            }

            $previousToken = previousMeaningfulToken($tokens, $index);
            $nextToken = nextMeaningfulToken($tokens, $index, $tokenCount);

            if ($nextToken !== '(' || in_array($previousToken, ['->', '::', 'function', 'new'], true)) {
                continue;
            }

            $violations[$file->getRelativePathname()][] = $functionName . '() on line ' . $token[2];
        }
    }

    ksort($violations);

    return $violations;
}

/**
 * @param  list<mixed>  $tokens
 */
function previousMeaningfulToken(array $tokens, int $currentIndex): ?string
{
    for ($index = $currentIndex - 1; $index >= 0; $index--) {
        $token = $tokens[$index];

        if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }

        if (is_array($token)) {
            return strtolower($token[1]);
        }

        return $token;
    }

    return null;
}

/**
 * @param  list<mixed>  $tokens
 */
function nextMeaningfulToken(array $tokens, int $currentIndex, int $tokenCount): ?string
{
    for ($index = $currentIndex + 1; $index < $tokenCount; $index++) {
        $token = $tokens[$index];

        if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }

        if (is_array($token)) {
            return strtolower($token[1]);
        }

        return $token;
    }

    return null;
}
