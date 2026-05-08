<?php

declare(strict_types=1);

namespace Capell\AccessGate\Support;

use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;

final class AccessGateSchema
{
    public static function builder(): Builder
    {
        $connection = config('access-gate.connection');

        if (is_string($connection) && $connection !== '') {
            return Schema::connection($connection);
        }

        return Schema::getFacadeRoot();
    }
}
