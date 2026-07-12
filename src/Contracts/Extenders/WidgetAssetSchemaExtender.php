<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts\Extenders;

use Capell\LayoutBuilder\Enums\SchemaExtenderEnum;
use Filament\Schemas\Schema;

interface WidgetAssetSchemaExtender
{
    public const string TAG = SchemaExtenderEnum::WidgetAsset->value;

    /**
     * @param  array<int, mixed>  $components
     * @return array<int, mixed>
     */
    public function extendAssetComponents(Schema $schema, array $components): array;

    /**
     * @param  array<int, mixed>  $components
     * @return array<int, mixed>
     */
    public function extendRepeaterComponents(array $components): array;
}
