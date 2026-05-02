<?php

declare(strict_types=1);

use Capell\DeveloperTools\Actions\Reports\SummarizeFailedJobExceptionAction;

it('summarizes a failed job exception without stack trace or payload details', function (): void {
    $summary = SummarizeFailedJobExceptionAction::run(
        "RuntimeException: Could not process webhook {\"token\":\"secret\"} in /var/www/html/app/Jobs/ProcessWebhook.php:42\n"
        . "#0 /var/www/html/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(59): App\\Jobs\\ProcessWebhook->handle()\n"
        . '#1 {main}',
    );

    expect($summary)->toBe('RuntimeException: Could not process webhook')
        ->and($summary)->not->toContain('/var/www/html')
        ->and($summary)->not->toContain('token')
        ->and($summary)->not->toContain('#0');
});

it('uses a safe fallback when no exception was stored', function (): void {
    expect(SummarizeFailedJobExceptionAction::run(null))->toBe('Unknown exception');
});
