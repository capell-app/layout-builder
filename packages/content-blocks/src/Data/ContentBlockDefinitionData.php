<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Data;

use BackedEnum;
use Capell\Admin\Filament\Contracts\FormConfigurator;

class ContentBlockDefinitionData
{
    /**
     * @param  class-string<FormConfigurator>  $configurator
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $description,
        public string|BackedEnum $icon,
        public string $group,
        public string $configurator,
        public string $component,
        public array $defaults = [],
    ) {}
}
