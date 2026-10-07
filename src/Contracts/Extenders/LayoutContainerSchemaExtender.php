<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts\Extenders;

use Capell\LayoutBuilder\Data\LayoutContainerSchemaContextData;
use Capell\LayoutBuilder\Enums\SchemaExtenderEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

interface LayoutContainerSchemaExtender
{
    public const string TAG = SchemaExtenderEnum::LayoutContainer->value;

    public function themeKey(): string;

    public function themeLabel(): string;

    public function supports(LayoutContainerSchemaContextData $context): bool;

    /**
     * @return array<int, Component | Action | ActionGroup | string | Htmlable>
     */
    public function extendContainerComponents(Schema $schema, LayoutContainerSchemaContextData $context): array;
}
