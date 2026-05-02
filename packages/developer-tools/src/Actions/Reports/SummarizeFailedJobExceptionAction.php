<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Actions\Reports;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static string run(?string $exception)
 */
final class SummarizeFailedJobExceptionAction
{
    use AsAction;

    public function handle(?string $exception): string
    {
        $summary = trim((string) Str::of($exception ?? '')->before("\n"));

        if ($summary === '') {
            return (string) __('capell-developer-tools::package.unknown_exception');
        }

        $summary = preg_replace('/\s+in\s+.+?:\d+$/', '', $summary) ?? $summary;
        $summary = preg_replace('/\s+\{.*\}$/', '', $summary) ?? $summary;
        $summary = preg_replace('/\s+/', ' ', $summary) ?? $summary;

        return Str::limit($summary, 240);
    }
}
