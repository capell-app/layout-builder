<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

final class User extends Authenticatable
{
    use HasFactory;

    protected $guarded = [];
}
