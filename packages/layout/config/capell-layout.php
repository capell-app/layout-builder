<?php

declare(strict_types=1);

use Capell\Layout\Filament\Resources\Pages\RelationManagers\ContentsRelationManager;
use Capell\Layout\Livewire\Assets\Table\ContentsTable;
use Capell\Layout\Livewire\Assets\Table\PagesTable;
use Capell\Layout\Livewire\Filament\WidgetAssetsTable;
use Capell\Layout\Livewire\LayoutBuilder;
use Capell\Layout\Livewire\Widget\PagesWidget;
use Capell\Layout\Models\Content;
use Capell\Layout\View\Components\Widget\Pages\ChildrenWidget;
use Capell\Layout\View\Components\Widget\Pages\LatestWidget;
use Capell\Layout\View\Components\Widget\Pages\RelatedWidget;
use Capell\Layout\View\Components\Widget\Pages\SiblingsWidget;

return [
    'assets' => [
        'content' => [
            'icon' => 'heroicon-o-gift',
            'model' => Content::class,
        ],
    ],
    'livewire_components' => [
        'capell.layout.livewire.layout-builder' => LayoutBuilder::class,
        'capell.layout.filament.resources.page-resource.relation-managers.contents-relation-manager' => ContentsRelationManager::class,
        'capell-layout::livewire.assets.table.page' => PagesTable::class,
        'capell-layout::livewire.assets.table.content' => ContentsTable::class,
        'capell-layout::filament.widget-assets-table' => WidgetAssetsTable::class,
        'capell-layout::livewire.widget.pages' => PagesWidget::class,
    ],
    'blade_components' => [
        'capell-layout::widget.pages.related' => RelatedWidget::class,
        'capell-layout::widget.pages.children' => ChildrenWidget::class,
        'capell-layout::widget.pages.siblings' => SiblingsWidget::class,
        'capell-layout::widget.pages.latest' => LatestWidget::class,
    ],
];
