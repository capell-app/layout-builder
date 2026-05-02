<?php

declare(strict_types=1);

namespace Capell\Mcp\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

final class User extends Authenticatable
{
    protected $guarded = [];
}
