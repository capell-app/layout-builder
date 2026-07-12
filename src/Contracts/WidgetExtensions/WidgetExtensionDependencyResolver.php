<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts\WidgetExtensions;

use Spatie\LaravelData\Data;

interface WidgetExtensionDependencyResolver
{
    /**
     * Resolve stable content-graph dependency identifiers such as `media:123`.
     *
     * @return list<string>
     */
    public function resolve(Data $input): array;
}
