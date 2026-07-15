<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

abstract class LayoutContainerThemePresentationData extends Data
{
    /**
     * @return list<string>
     */
    abstract public function classes(): array;
}
