<?php

declare(strict_types=1);

// Suppress fatal errors during bootstrapping that are not relevant to code analysis
if (! defined('PHPSTAN_ERROR_HANDLER_SET')) {
    define('PHPSTAN_ERROR_HANDLER_SET', true);
    set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
        // Suppress Page::trashed() signature mismatch in core package during bootstrap
        if (str_contains($errstr, 'Declaration of Capell\\Core\\Models\\Page::trashed()')) {
            return true;
        }

        return false;
    });
}

namespace Capell\Admin\Contracts\Extenders;

interface PageHeaderActionExtender
{
    public const TAG = 'capell-admin:page-header-actions';

    /** @return array<int, object> */
    public function actions(): array;
}

interface SiteHeaderActionExtender
{
    public const TAG = 'capell-admin:site-header-actions';

    /** @return array<int, object> */
    public function actions(): array;
}
