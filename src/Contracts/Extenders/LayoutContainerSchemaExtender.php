<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts\Extenders;

use Capell\LayoutBuilder\Data\LayoutContainerSchemaContextData;
use Capell\LayoutBuilder\Enums\SchemaExtenderEnum;
use Filament\Schemas\Schema;

interface LayoutContainerSchemaExtender
{
    public const string TAG = SchemaExtenderEnum::LayoutContainer->value;

    public function themeKey(): string;

    public function themeLabel(): string;

    public function supports(LayoutContainerSchemaContextData $context): bool;

    /**
     * @return array<int, mixed>
     */
    public function extendContainerComponents(Schema $schema, LayoutContainerSchemaContextData $context): array;
}
