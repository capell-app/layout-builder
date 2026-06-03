<?php

declare(strict_types=1);

use Capell\Admin\Enums\CapellPermission;
use Capell\Core\Enums\PresentationWidthMode;
use Capell\Core\Models\Blueprint;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Pages\EditWidget;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class)
    ->group('widget');

beforeEach(function (): void {
    resolve(LayoutBuilderAdminRegistrar::class)->register();
    test()->actingAsAdmin();
    Permission::create([
        'name' => CapellPermission::ManageAdvancedPresentationSettings->name(),
        'guard_name' => 'web',
    ]);
    auth()->user()?->givePermissionTo(CapellPermission::ManageAdvancedPresentationSettings->name());
});

test('widget type select edit action preserves type identity and saves presentation settings', function (): void {
    $type = Blueprint::factory()->create([
        'name' => 'System',
        'key' => 'system',
        'type' => 'widget',
        'admin' => [
            'type_configurator' => 'Widget',
            'configurator' => 'System',
            'layout_widget_configurator' => 'Default',
        ],
        'meta' => [
            'component' => 'capell.layout-builder.widget.default',
            'resource_groups' => ['system'],
        ],
    ]);

    $widget = Widget::factory()->create([
        'name' => 'Breadcrumbs',
        'key' => 'breadcrumbs',
        'blueprint_id' => $type->getKey(),
    ]);

    $livewire = Livewire::test(EditWidget::class, [
        'record' => $widget->getRouteKey(),
    ])
        ->mountAction(TestAction::make('editOption')->schemaComponent('blueprint_id', schema: 'form'))
        ->set('mountedActions.0.data.name', 'Changed name')
        ->set('mountedActions.0.data.key', 'changed-key')
        ->set('mountedActions.0.data.type', 'page')
        ->set('mountedActions.0.data.meta.presentation.width_mode', PresentationWidthMode::Container->value);

    $livewire
        ->callMountedAction()
        ->assertHasNoFormErrors();

    $type->refresh();

    expect($type->name)->toBe('Changed name')
        ->and($type->key)->toBe('changed-key')
        ->and($type->getRawOriginal('type'))->toBe('widget')
        ->and($type->component)->toBe('capell.layout-builder.widget.default')
        ->and($type->meta)->toMatchArray([
            'resource_groups' => ['system'],
        ])
        ->and(data_get($type->meta, 'presentation.width_mode'))->toBe(PresentationWidthMode::Container->value);
});

test('widget type cannot be reassigned from the edit form payload', function (): void {
    $type = Blueprint::factory()->create([
        'type' => 'widget',
        'admin' => [
            'type_configurator' => 'Widget',
        ],
    ]);
    $otherType = Blueprint::factory()->create([
        'type' => 'widget',
        'admin' => [
            'type_configurator' => 'Widget',
        ],
    ]);
    $widget = Widget::factory()->create([
        'blueprint_id' => $type->getKey(),
    ]);

    Livewire::test(EditWidget::class, [
        'record' => $widget->getRouteKey(),
    ])
        ->fillForm([
            'blueprint_id' => $otherType->getKey(),
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($widget->refresh()->blueprint_id)->toBe($type->getKey());
});
