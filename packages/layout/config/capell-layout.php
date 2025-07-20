<?php

declare(strict_types=1);

use Capell\Layout\Filament\Resources\PageResource\RelationManagers\ContentsRelationManager;
use Capell\Layout\Livewire\Assets\Table\ContentsTable;
use Capell\Layout\Livewire\Assets\Table\MediaTable;
use Capell\Layout\Livewire\Assets\Table\PagesTable;
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
            'icon' => 'heroicon-o-document-text',
            'model' => Content::class,
        ],
    ],
    'livewire_components' => [
        'capell.layout.livewire.layout-builder' => LayoutBuilder::class,
        'capell.layout.filament.resources.page-resource.relation-managers.contents-relation-manager' => ContentsRelationManager::class,
        'capell-layout::layout-builder-assets-table-media' => MediaTable::class,
        'capell-layout::layout-builder-assets-table-page' => PagesTable::class,
        'capell-layout::layout-builder-assets-table-content' => ContentsTable::class,
        'capell-layout::livewire.widget.pages' => PagesWidget::class,
    ],

    'blade_components' => [
        'capell-layout::widget.pages.related' => RelatedWidget::class,
        'capell-layout::widget.pages.children' => ChildrenWidget::class,
        'capell-layout::widget.pages.siblings' => SiblingsWidget::class,
        'capell-layout::widget.pages.latest' => LatestWidget::class,
    ],
];
