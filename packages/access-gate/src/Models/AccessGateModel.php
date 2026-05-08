<?php

declare(strict_types=1);

namespace Capell\AccessGate\Models;

use Illuminate\Database\Eloquent\Model;

abstract class AccessGateModel extends Model
{
    public function getConnectionName(): ?string
    {
        $connection = config('access-gate.connection');

        return is_string($connection) && $connection !== '' ? $connection : parent::getConnectionName();
    }
}
