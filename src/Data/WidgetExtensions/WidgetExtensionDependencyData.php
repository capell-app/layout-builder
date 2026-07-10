<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data\WidgetExtensions;

use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

final class WidgetExtensionDependencyData extends Data
{
    /** @param class-string<Model> $modelType */
    public function __construct(
        public string $modelType,
        public int $modelId,
        public string $kind,
    ) {}
}
