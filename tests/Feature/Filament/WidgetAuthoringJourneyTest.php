<?php

declare(strict_types=1);

use Capell\Core\Enums\BlueprintGroupEnum;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Filament\Components\Forms\AssetsRepeater;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Pages\ListWidgets;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\Tests\Fixtures\Models\User;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Tests\Support\SchemaStateHarness;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class);

it('lets editors find reusable widgets in the language they can see on the translated row', function (): void {
    $this->actingAsAdmin();
    $english = Language::factory()->create(['locale' => 'en', 'code' => 'en', 'default' => true]);
    $french = Language::factory()->create(['locale' => 'fr', 'code' => 'fr']);
    $englishWidget = Widget::factory()->create(['name' => 'Shipping notice']);
    $frenchWidget = Widget::factory()->create(['name' => 'Delivery notice']);
    $englishWidget->translations()->create(['language_id' => $english->getKey(), 'title' => 'Shipping details', 'content' => 'Sent within three working days']);
    $frenchWidget->translations()->create(['language_id' => $french->getKey(), 'title' => 'Aide livraison', 'content' => 'Livraison sous trois jours']);

    Livewire::test(ListWidgets::class)
        ->toggleAllTableColumns()
        ->filterTable('filter', ['language_id' => $english->getKey()])
        ->assertSee('Sent within three working days')
        ->assertCanSeeTableRecords([$englishWidget])
        ->assertCanNotSeeTableRecords([$frenchWidget])
        ->filterTable('filter', ['language_id' => $french->getKey()])
        ->assertCanSeeTableRecords([$frenchWidget])
        ->assertCanNotSeeTableRecords([$englishWidget]);
});

it('excludes internal pages from asset search and creates a usable page through the inline form', function (): void {
    $this->actingAsAdmin();
    $site = Site::factory()->create(['name' => 'Shop help']);
    $regular = Blueprint::factory()->create(['group' => null]);
    $system = Blueprint::factory()->create(['group' => BlueprintGroupEnum::System->value]);
    $orders = Page::factory()->create(['name' => 'Searchable orders', 'site_id' => $site->getKey(), 'blueprint_id' => $regular->getKey()]);
    $systemPage = Page::factory()->create(['name' => 'Searchable diagnostics', 'site_id' => $site->getKey(), 'blueprint_id' => $system->getKey()]);
    $archivedPage = Page::factory()->create(['name' => 'Searchable old orders', 'site_id' => $site->getKey(), 'blueprint_id' => $regular->getKey()]);
    $archivedPage->delete();

    // A relationship repeater on a saved widget loads its items from the widget's assets, so the
    // asset is persisted rather than filled as raw state, which the relationship would replace.
    $widget = Widget::factory()->create();
    WidgetAsset::factory()->widget($widget)->asset($archivedPage)->create();

    $repeater = AssetsRepeater::make('widgetAssets');
    Schema::make(new SchemaStateHarness)
        ->operation('edit')
        ->statePath('data')
        ->model($widget)
        ->components([$repeater])
        ->fill([]);

    $key = array_key_first($repeater->getChildSchemas());
    assert(is_string($key));
    $item = $repeater->getChildSchema($key);
    assert($item instanceof Schema);
    $item->model(WidgetAsset::factory()->asset($archivedPage)->make());
    $type = $item->getComponent('asset_type', withHidden: true);
    assert($type instanceof Hidden);
    $type->state('page');
    $select = $item->getComponent('asset_id');
    assert($select instanceof Select);

    expect($select->getSearchResults('Searchable'))
        ->toHaveKey($orders->id)
        ->not->toHaveKeys([$systemPage->id, $archivedPage->id]);

    $pending = Page::factory()->make(['name' => 'Searchable returns guide', 'site_id' => $site->getKey(), 'blueprint_id' => $regular->getKey()]);
    $form = Schema::make(new SchemaStateHarness)->statePath('mountedActions.0.data')->model(Page::class);
    $type->state('page');
    expect($select->getGetCallback()('asset_type'))->toBe('page');
    $createdId = $select->evaluate($select->getCreateOptionUsing(), [
        'component' => $select,
        'get' => $select->getGetCallback(),
        'data' => Arr::except($pending->getAttributes(), ['created_at', 'updated_at']),
        'schema' => $form,
        'form' => $form,
    ]);

    assert(is_int($createdId) || is_string($createdId));
    $created = Page::query()->whereKey($createdId)->sole();
    expect($created->name)->toBe('Searchable returns guide')
        ->and($created->site_id)->toBe($site->getKey());

    expect($select->getSearchResults('Searchable returns guide'))->toHaveKey($created->id);
});

it('reports real placement impact before an editor deletes a widget', function (bool $placed): void {
    $this->actingAsAdmin();
    $widget = Widget::factory()->create();

    if ($placed) {
        Layout::factory()->create(['containers' => ['main' => ['widgets' => [
            ['widget_key' => $widget->key, 'occurrence' => 1],
        ]]]]);
    }

    $component = Livewire::test(ListWidgets::class)
        ->mountAction(TestAction::make('delete')->table($widget));
    $modal = data_get($component->__get('effects'), 'partials.action-modals');
    assert(is_string($modal));

    $placedWarning = trans_choice('capell-layout-builder::message.widget_delete_impact_layouts', 1, ['count' => 1]);
    $unusedWarning = (string) __('capell-layout-builder::message.widget_delete_impact_unused');
    expect($modal)->toContain($placed ? $placedWarning : $unusedWarning)
        ->not->toContain($placed ? $unusedWarning : $placedWarning);
    expect($widget->refresh()->trashed())->toBeFalse();
})->with(['orphan' => [false], 'placed' => [true]]);

it('does not claim a widget is unused when the editor cannot see its layouts', function (): void {
    $editor = User::factory()->create();
    $permissions = array_map(Permission::findOrCreate(...), ['ViewAny:Widget', 'View:Widget', 'Delete:Widget']);
    $editor->givePermissionTo($permissions);
    $this->actingAs($editor);
    $widget = Widget::factory()->create();
    Layout::factory()->create(['containers' => ['main' => ['widgets' => [
        ['widget_key' => $widget->key, 'occurrence' => 1],
    ]]]]);

    $component = Livewire::test(ListWidgets::class)
        ->mountAction(TestAction::make('delete')->table($widget));
    $modal = data_get($component->__get('effects'), 'partials.action-modals');
    assert(is_string($modal));

    expect($modal)->toContain((string) __('capell-layout-builder::message.widget_delete_impact_no_tracked_uses'))
        ->not->toContain((string) __('capell-layout-builder::message.widget_delete_impact_unused'))
        ->not->toContain(trans_choice('capell-layout-builder::message.widget_delete_impact_layouts', 1, ['count' => 1]));
});
