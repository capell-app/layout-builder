<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Layout\Filament\Resources\WidgetResource;
use Capell\Layout\Models\Widget;

use function Pest\Livewire\livewire;

it('can list layouts for a widget', function (): void {
    $widget = Widget::factory()->create();

    Layout::factory()
        ->state([
            'containers' => [
                'main' => [
                    'widgets' => [
                        ['widget_key' => $widget->key],
                    ],
                ],
            ],
        ])
        ->count(5)
        ->create();

    $layout = $widget->layouts->first();

    livewire(WidgetResource\RelationManagers\LayoutsRelationManager::class, [
        'ownerRecord' => $widget,
        'pageClass' => WidgetResource\Pages\EditWidget::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($widget->layouts)
        ->assertTableColumnStateSet('name', [$layout->name], record: $layout);
});

it('can filter layouts by site', function (): void {
    $site = Site::factory()->create();
    $widget = Widget::factory()->create();

    Layout::factory()
        ->state([
            'containers' => [
                'main' => [
                    'widgets' => [
                        ['widget_key' => $widget->key],
                    ],
                ],
            ],
        ])
        ->sequence(
            ['site_id' => $site->getKey()],
            ['site_id' => Site::factory()->create()->getKey()],
        )
        ->count(2)
        ->create();

    livewire(WidgetResource\RelationManagers\LayoutsRelationManager::class, [
        'ownerRecord' => $widget,
        'pageClass' => WidgetResource\Pages\EditWidget::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(2)
        ->filterTable('site_id', $site->getKey())
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords($widget->layouts->where('site_id', $site->getKey()));
});
