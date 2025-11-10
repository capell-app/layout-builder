<?php

declare(strict_types=1);

use Capell\Layout\Filament\Resources\Pages\RelationManagers\ContentsRelationManager;
use Capell\Layout\Filament\Resources\Widgets\RelationManagers\WidgetAssetsRelationManager;
use Capell\Layout\Livewire\Assets\Table\ContentsTable;
use Capell\Layout\Livewire\Assets\Table\PagesTable;
use Capell\Layout\Livewire\LayoutBuilder;
use Capell\Layout\Livewire\Widget\PagesWidget;
use Capell\Layout\Models\Content;
use Capell\Layout\View\Components\Widget\Pages\ChildrenWidget;
use Capell\Layout\View\Components\Widget\Pages\LatestWidget;
use Capell\Layout\View\Components\Widget\Pages\SiblingsWidget;
use Filament\Support\Icons\Heroicon;

return [
    'assets' => [
        'content' => [
            'icon' => Heroicon::OutlinedRectangleStack,
            'model' => Content::class,
        ],
    ],
    'livewire_components' => [
        'capell.layout.livewire.layout-builder' => LayoutBuilder::class,
        'capell.layout.filament.resources.page-resource.relation-managers.contents-relation-manager' => ContentsRelationManager::class,
        'capell.layout.filament.resources.widget-resource.relation-managers.widget-assets-relation-manager' => WidgetAssetsRelationManager::class,
        'capell-layout::livewire.assets.table.page' => PagesTable::class,
        'capell-layout::livewire.assets.table.content' => ContentsTable::class,
        'capell-layout::livewire.widget.pages' => PagesWidget::class,
    ],
    'blade_components' => [
        'capell-layout::widget.pages.children' => ChildrenWidget::class,
        'capell-layout::widget.pages.siblings' => SiblingsWidget::class,
        'capell-layout::widget.pages.latest' => LatestWidget::class,
    ],
    'widget' => [
        'hide_empty' => false,
    ],
    'layout_builder' => [
        'lazy' => env('CAPELL_LAYOUT_BUILDER_LAZY', true),
    ],
];
