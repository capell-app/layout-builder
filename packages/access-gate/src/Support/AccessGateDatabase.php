<?php

declare(strict_types=1);

namespace Capell\AccessGate\Support;

use Closure;
use Illuminate\Support\Facades\DB;

final class AccessGateDatabase
{
    public static function connectionName(): ?string
    {
        $connection = config('access-gate.connection');

        return is_string($connection) && $connection !== '' ? $connection : null;
    }

    public static function transaction(Closure $callback): mixed
    {
        $connection = self::connectionName();

        if ($connection !== null) {
            return DB::connection($connection)->transaction($callback);
        }

        return DB::transaction($callback);
    }
}
