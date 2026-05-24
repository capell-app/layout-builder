<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts\Extenders;

use Capell\LayoutBuilder\Enums\SchemaExtenderEnum;
use Filament\Schemas\Schema;

interface BlockSchemaExtender
{
    public const string TAG = SchemaExtenderEnum::Widget->value;

    /**
     * @param  array<int, mixed>  $components
     * @return array<int, mixed>
     */
    public function extendDisplayComponents(Schema $schema, array $components): array;
}
