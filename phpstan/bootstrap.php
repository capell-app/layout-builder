<?php

declare(strict_types=1);

// Suppress fatal errors during bootstrapping that are not relevant to code analysis
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    // Suppress Page::trashed() signature mismatch in core package during bootstrap
    if (str_contains($errstr, 'Declaration of Capell\\Core\\Models\\Page::trashed()')) {
        return true;
    }

    // Let other errors pass through
    return false;
});

register_shutdown_function(function (): void {
    restore_error_handler();
});
