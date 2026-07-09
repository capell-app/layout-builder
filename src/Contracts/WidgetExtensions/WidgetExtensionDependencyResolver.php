<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts\WidgetExtensions;

interface WidgetExtensionDependencyResolver
{
    /**
     * Resolve stable content-graph dependency identifiers such as `media:123`.
     *
     * @param  array<string, mixed>  $state
     * @return list<string>
     */
    public function resolve(array $state): array;
}
