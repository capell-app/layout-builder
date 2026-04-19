<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Accessibility;

class AriaHelper
{
    public function labelledBy(string $id): string
    {
        return 'aria-labelledby="' . $id . '"';
    }

    public function describedBy(string $id): string
    {
        return 'aria-describedby="' . $id . '"';
    }

    public function expanded(bool $value): string
    {
        return 'aria-expanded="' . ($value ? 'true' : 'false') . '"';
    }

    public function hidden(bool $value): string
    {
        return 'aria-hidden="' . ($value ? 'true' : 'false') . '"';
    }

    public function live(string $value): string
    {
        return 'aria-live="' . $value . '"';
    }

    public function uniqueId(string $prefix): string
    {
        return $prefix . '-' . bin2hex(random_bytes(2));
    }

    public function role(string $value): string
    {
        return 'role="' . $value . '"';
    }

    public function controls(string $id): string
    {
        return 'aria-controls="' . $id . '"';
    }

    public function current(string $value): string
    {
        return 'aria-current="' . $value . '"';
    }
}
