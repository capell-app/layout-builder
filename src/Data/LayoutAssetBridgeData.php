<?php

declare(strict_types=1);

namespace Capell\Mosaic\Data;

use BackedEnum;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Core\Contracts\Actionable;
use Illuminate\Database\Eloquent\Model;

class LayoutAssetBridgeData
{
    /**
     * @param  class-string<Model>  $model
     * @param  class-string<FormConfigurator>  $formClass
     * @param  class-string<Actionable>  $createAction
     * @param  class-string<Actionable>  $defaultDataAction
     * @param  class-string|null  $livewireTable
     */
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly string $model,
        public readonly string|BackedEnum $icon,
        public readonly string $color,
        public readonly string $label,
        public readonly string $component,
        public readonly string $formClass,
        public readonly string $createAction,
        public readonly string $defaultDataAction,
        public readonly bool $hasTranslations,
        public readonly ?string $livewireTable = null,
    ) {}
}
