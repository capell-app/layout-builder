<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Pages\Schemas\Extenders;

use Capell\Admin\Contracts\Extenders;
use Capell\Admin\Enums\PageTranslationSchemaHookEnum;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Filament\Components\Forms\Page\Tab\LayoutTab;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class PageSchemaExtender implements Extenders\PageSchemaExtender
{
    public function extendRelationManagers(Model $record, array $relationManagers): array
    {
        return $relationManagers;
    }

    public function extendTabs(Schema $configurator, array $tabs): array
    {
        if (! $this->isLayoutEditable($configurator)) {
            return $tabs;
        }

        $hasLayoutTab = collect($tabs)->contains(
            fn (Tab $tab): bool => $tab->getLabel() === __('capell-admin::tab.layout'),
        );

        if (! $hasLayoutTab) {
            array_unshift($tabs, LayoutTab::make());
        }

        return $tabs;
    }

    /**
     * @return array<int, Component>
     */
    public function extendTranslationComponentsForHook(Schema $configurator, PageTranslationSchemaHookEnum $hook): array
    {
        return [];
    }

    /**
     * @return array<int, Component>
     */
    public function extendSidebarComponents(Schema $configurator): array
    {
        return [];
    }

    private function isLayoutEditable(Schema $configurator): bool
    {
        $record = $configurator->getRecord();

        if (! $record instanceof Pageable) {
            return true;
        }

        $type = $record->getRelationValue('type');

        if (! $type instanceof Blueprint) {
            return true;
        }

        if ($type->getMeta('layout_editable') === false) {
            return false;
        }

        $layout = $record->getRelationValue('layout');

        if (! $layout instanceof Layout) {
            return true;
        }

        return data_get($layout->admin ?? [], 'disable_layout_builder') !== true;
    }
}
