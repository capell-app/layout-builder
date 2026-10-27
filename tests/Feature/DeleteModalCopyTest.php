<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Filament\Resources\Widgets\Pages\EditWidget;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Pages\ListWidgets;
use Capell\LayoutBuilder\Filament\Resources\Widgets\RelationManagers\WidgetAssetsRelationManager;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Livewire\Livewire;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

uses(CreatesAdminUser::class);

/**
 * @param  class-string<Component>  $page
 * @param  Closure(): Model  $makeRecord
 */
it('renders translated delete consequences in the real admin surface', function (string $page, Closure $makeRecord, string $mode, string $actionName, string $translation, bool $force): void {
    test()->actingAsAdmin();
    $heading = __($translation . '.heading');
    $description = __($translation . '.description');

    expect($heading)->toBeString()->not->toBeEmpty()->not->toBe($translation . '.heading')
        ->and($description)->toBeString()->not->toBeEmpty()->not->toBe($translation . '.description');

    $record = $makeRecord();

    $parameters = $mode === 'page' ? ['record' => $record->getRouteKey()] : [];
    if ($page === WidgetAssetsRelationManager::class) {
        $parameters = ['ownerRecord' => Widget::query()->findOrFail($record->getAttribute('widget_id')), 'pageClass' => EditWidget::class];
    }

    $component = Livewire::test($page, $parameters)->assertSuccessful();
    if ($page === WidgetAssetsRelationManager::class) {
        $component->call('loadTable')->assertCanSeeTableRecords([$record]);
    }

    if ($force) {
        $record->delete();
        $surface = $component->instance();
        if ($mode === 'page') {
            throw_unless($surface instanceof HasActions, LogicException::class);
            $action = $surface->getAction($actionName);
        } else {
            throw_unless($surface instanceof HasTable, LogicException::class);
            $action = $surface->getTable()->getBulkAction($actionName);
        }

        throw_unless($action instanceof Action, LogicException::class);
        // Render the real action in its page context without rehydrating a trashed route record.
        $blade = resolve(ExtendBlade::class);
        $blade->startLivewireRendering($surface);
        try {
            $html = $action->record($record)->toModalHtmlable()->toHtml();
        } finally {
            $blade->endLivewireRendering();
        }

        expect($html)->toContain(e($heading), e($description));

        return;
    }

    if ($mode === 'page') {
        $component->mountAction($actionName);
    } else {
        if ($mode === 'bulk') {
            $component->mountTableBulkAction($actionName, [$record->getKey()]);
        } else {
            $component->mountTableAction($actionName, $record);
        }
    }

    $component->assertMountedActionModalSee($heading)->assertMountedActionModalSee($description);
})->with([
    'EditWidget DeleteAction' => [EditWidget::class, fn (): Model => Widget::factory()->create(), 'page', 'delete', 'capell-layout-builder::widget.delete_modal', false],
    'EditWidget ForceDeleteAction' => [EditWidget::class, fn (): Model => Widget::factory()->create(), 'page', 'forceDelete', 'capell-layout-builder::widget.force_delete_modal', true],
    'WidgetAssetsTable DeleteAction' => [WidgetAssetsRelationManager::class, fn (): Model => WidgetAsset::factory()->create(), 'table', 'delete', 'capell-layout-builder::widget_asset.delete_modal', false],
    'WidgetAssetsTable DeleteBulkAction' => [WidgetAssetsRelationManager::class, fn (): Model => WidgetAsset::factory()->create(), 'bulk', 'delete', 'capell-layout-builder::widget_asset.delete_bulk_modal', false],
    'WidgetsTable DeleteAction' => [ListWidgets::class, fn (): Model => Widget::factory()->create(), 'table', 'delete', 'capell-layout-builder::widget.delete_modal', false],
    'WidgetsTable DeleteBulkAction' => [ListWidgets::class, fn (): Model => Widget::factory()->create(), 'bulk', 'delete', 'capell-layout-builder::widget.delete_bulk_modal', false],
    'WidgetsTable ForceDeleteBulkAction' => [ListWidgets::class, fn (): Model => Widget::factory()->create(), 'bulk', 'forceDelete', 'capell-layout-builder::widget.force_delete_bulk_modal', true],
]);
