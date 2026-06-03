<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

use Capell\Core\Contracts\Actionable;

final class LayoutBuilderAdminRegistrationActionable implements Actionable
{
    public static function run(mixed ...$parameters): mixed
    {
        return $parameters;
    }
}
