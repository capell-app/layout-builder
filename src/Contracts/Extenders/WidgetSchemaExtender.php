<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts\Extenders;

use Capell\LayoutBuilder\Enums\SchemaExtenderEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

interface WidgetSchemaExtender
{
    public const string TAG = SchemaExtenderEnum::Widget->value;

    /**
     * @param  array<int, Component | Action | ActionGroup | string | Htmlable>  $components
     * @return array<int, Component | Action | ActionGroup | string | Htmlable>
     */
    public function extendDisplayComponents(Schema $schema, array $components): array;
}
