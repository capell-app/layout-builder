<?php

declare(strict_types=1);

namespace Capell\Navigation\Filament\Extenders;

use Capell\Admin\Contracts\Extenders\SiteSchemaExtender;
use Capell\Admin\Enums\PageTranslationSchemaHookEnum;
use Capell\Admin\Enums\SiteCreateWizardHookEnum;
use Capell\Navigation\Filament\Resources\Sites\RelationManagers\NavigationsRelationManager;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class NavigationSiteExtender implements SiteSchemaExtender
{
    public function extendRelationManagers(Model $record, array $relationManagers): array
    {
        $relationManagers[] = NavigationsRelationManager::class;

        return $relationManagers;
    }

    public function extendTabs(Schema $schema, array $tabs): array
    {
        return $tabs;
    }

    public function extendTranslationComponentsForHook(Schema $schema, PageTranslationSchemaHookEnum $hook): array
    {
        return [];
    }

    public function extendSiteMetaDetailsComponents(Schema $schema, array $components): array
    {
        return $components;
    }

    public function extendCreateWizardComponentsForHook(Schema $schema, SiteCreateWizardHookEnum $hook): array
    {
        return [];
    }
}
