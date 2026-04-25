<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Resources\Sites\Schemas\Extenders;

use Capell\Address\Filament\Components\Forms\AddressSelect;
use Capell\Admin\Contracts\Extenders;
use Capell\Admin\Enums\PageTranslationSchemaHookEnum;
use Capell\Admin\Enums\SiteCreateWizardHookEnum;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class SiteSchemaExtender implements Extenders\SiteSchemaExtender
{
    public function extendRelationManagers(Model $record, array $relationManagers): array
    {
        return $relationManagers;
    }

    public function extendTabs(Schema $schema, array $tabs): array
    {
        return $tabs;
    }

    public function extendTranslationComponentsForHook(Schema $schema, PageTranslationSchemaHookEnum $hook): array
    {
        // For backward compatibility, return empty: address package previously mutated
        // the full components array directly. If needed, implement hook-aware insertion.
        return [];
    }

    public function extendSiteMetaDetailsComponents(Schema $schema, array $components): array
    {
        $components[] = $this->getAddressSelect($schema);

        return $components;
    }

    public function extendCreateWizardComponentsForHook(Schema $schema, SiteCreateWizardHookEnum $hook): array
    {
        return [];
    }

    private function getAddressSelect(Schema $schema): AddressSelect
    {
        return AddressSelect::make('address_id')
            ->columnSpanFull()
            ->when(
                $schema->isCreating(),
                fn (AddressSelect $component): AddressSelect => $component->withCreateForm(),
                fn (AddressSelect $component): AddressSelect => $component->withEditForm(),
            );
    }
}
