<?php

declare(strict_types=1);

namespace Capell\Backup\Contracts;

use Closure;

interface BackupContextResolver
{
    /**
     * Execute $callback inside any ambient scope the resolver manages.
     * The resolver decides what state to set up before the callback and
     * tears it down after. Core passes no parameters describing the scope.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function wrap(Closure $callback): mixed;
}
