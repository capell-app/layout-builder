<?php

declare(strict_types=1);

return [
    'assets' => [
        'content' => [
            'icon' => 'heroicon-o-document-text',
            'model' => Capell\Layout\Models\Content::class,
        ],
    ],
    'livewire_components' => [
        'capell.layout.livewire.layout-builder' => Capell\Layout\Livewire\LayoutBuilder::class,
        'capell.layout.filament.resources.page-resource.relation-managers.contents-relation-manager' => Capell\Layout\Filament\Resources\PageResource\RelationManagers\ContentsRelationManager::class,
        'capell-layout::layout-builder-assets-table-media' => Capell\Layout\Livewire\Assets\Table\MediaTable::class,
        'capell-layout::layout-builder-assets-table-page' => Capell\Layout\Livewire\Assets\Table\PagesTable::class,
        'capell-layout::layout-builder-assets-table-content' => Capell\Layout\Livewire\Assets\Table\ContentsTable::class,
        'capell-layout::livewire.widget.pages' => Capell\Layout\Livewire\Widget\PagesWidget::class,
    ],

    'blade_components' => [
        'capell-layout::widget.pages.related' => Capell\Layout\View\Components\Widget\Pages\RelatedWidget::class,
        'capell-layout::widget.pages.children' => Capell\Layout\View\Components\Widget\Pages\ChildrenWidget::class,
        'capell-layout::widget.pages.siblings' => Capell\Layout\View\Components\Widget\Pages\SiblingsWidget::class,
        'capell-layout::widget.pages.latest' => Capell\Layout\View\Components\Widget\Pages\LatestWidget::class,
    ],
];
