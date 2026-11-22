<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Filament\Components\Forms\AssetsRepeater;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Tests\Support\SchemaStateHarness;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

uses(CreatesAdminUser::class);

it('distinguishes identically titled pages by site and ancestor in the asset picker', function (): void {
    $this->actingAsAdmin();
    $store = Site::factory()->create(['name' => 'Store']);
    $support = Site::factory()->create(['name' => 'Support']);
    $orders = Page::factory()->create(['name' => 'Orders', 'site_id' => $store->getKey()]);
    $account = Page::factory()->create(['name' => 'Account', 'site_id' => $support->getKey()]);
    $storeReturns = Page::factory()->create(['name' => 'Returns', 'site_id' => $store->getKey()]);
    $supportReturns = Page::factory()->create(['name' => 'Returns', 'site_id' => $support->getKey()]);
    $storeReturns->appendToNode($orders)->save();
    $supportReturns->appendToNode($account)->save();

    // A relationship repeater on a saved widget loads its items from the widget's assets, so the
    // asset is persisted rather than filled as raw state, which the relationship would replace.
    $widget = Widget::factory()->create();
    WidgetAsset::factory()->widget($widget)->asset($storeReturns)->create();

    $repeater = AssetsRepeater::make('widgetAssets');
    Schema::make(new SchemaStateHarness)
        ->operation('edit')
        ->statePath('data')
        ->model($widget)
        ->components([$repeater])
        ->fill([]);

    $key = array_key_first($repeater->getChildSchemas());
    assert(is_string($key));
    $select = $repeater->getChildSchema($key)?->getComponent('asset_id');
    assert($select instanceof Select);

    expect($select->getOptionLabelFromRecord($storeReturns))->toBe('Store &raquo; Orders &raquo; Returns')
        ->and($select->getOptionLabelFromRecord($supportReturns))->toBe('Support &raquo; Account &raquo; Returns');
});
