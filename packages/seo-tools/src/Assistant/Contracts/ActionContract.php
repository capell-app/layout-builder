<?php

declare(strict_types=1);

namespace Capell\SeoTools\Assistant\Contracts;

interface ActionContract
{
    public function handle(...$args): mixed;

    public function validate(array $input): bool;
}
