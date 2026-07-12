<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Schemas\Components\Component as SchemaComponent;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Contracts\TranslatableContentDriver;
use Livewire\Component;

final class LayoutBuilderActionSchemaHarness extends Component implements HasSchemas
{
    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }

    public function getOldSchemaState(string $statePath): mixed
    {
        unset($statePath);

        return null;
    }

    /**
     * @param  array<SchemaComponent>  $skipComponentsChildContainersWhileSearching
     */
    public function getSchemaComponent(string $key, bool $withHidden = false, array $skipComponentsChildContainersWhileSearching = []): SchemaComponent|Action|ActionGroup|null
    {
        unset($key, $withHidden, $skipComponentsChildContainersWhileSearching);

        return null;
    }

    public function getSchema(string $name): ?Schema
    {
        unset($name);

        return null;
    }

    public function currentlyValidatingSchema(?Schema $schema): void
    {
        unset($schema);
    }

    public function getDefaultTestingSchemaName(): ?string
    {
        return null;
    }

    public function getResource(): null
    {
        return null;
    }
}
