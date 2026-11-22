<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Enums\LayoutBulkChangeRunStatus;
use Capell\LayoutBuilder\Enums\LayoutBulkWidgetOperationType;
use Capell\LayoutBuilder\Filament\Resources\Layouts\Pages\ListLayouts;
use Capell\LayoutBuilder\Jobs\ApplyLayoutBulkChangeRunJob;
use Capell\LayoutBuilder\Models\LayoutBulkChangeRun;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\Testing\TestAction;
use Filament\Schemas\Components\Wizard;
use Illuminate\Support\Facades\Bus;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

uses(CreatesAdminUser::class);

it('previews only selected layouts before removing the approved occurrence or queueing a large change', function (int $targetCount): void {
    $this->actingAsAdmin();
    Bus::fake([ApplyLayoutBulkChangeRunJob::class]);
    $widget = Widget::factory()->create(['name' => 'Delivery banner']);
    $placements = [
        ['widget_key' => $widget->key, 'occurrence' => 1, 'container' => 'main'],
        ['widget_key' => $widget->key, 'occurrence' => 2, 'container' => 'main'],
    ];
    $selected = Layout::factory()->count($targetCount)->create(['status' => true, 'containers' => ['main' => ['widgets' => $placements]]]);
    $excluded = Layout::factory()->create(['status' => true, 'containers' => ['main' => ['widgets' => $placements]]]);

    $component = Livewire::test(ListLayouts::class)
        ->mountAction(TestAction::make('bulkChangeLayouts')->table())
        ->fillForm([
            'layout_keys' => $selected->pluck('key')->all(),
            'require_widget_key' => $widget->key,
            'operation_type' => LayoutBulkWidgetOperationType::RemoveWidget->value,
            'source_widget_key' => $widget->key,
            'source_container_key' => 'main',
            'occurrence_mode' => 'specific',
            'source_occurrence_number' => 2,
        ]);

    advanceLayoutBulkChangeWizard($component);

    $run = LayoutBulkChangeRun::query()->sole();
    expect(data_get($run->summary, 'target_layouts'))->toBe($targetCount)
        ->and(data_get($selected->firstOrFail()->refresh()->containers, 'main.widgets'))->toBe($placements);

    $component->callMountedAction()->assertHasNoActionErrors();

    if ($targetCount === 50) {
        Bus::assertDispatched(ApplyLayoutBulkChangeRunJob::class);
        expect($run->refresh()->status)->toBe(LayoutBulkChangeRunStatus::Queued)
            ->and(data_get($selected->firstOrFail()->refresh()->containers, 'main.widgets'))->toBe($placements);
    } else {
        Bus::assertNotDispatched(ApplyLayoutBulkChangeRunJob::class);
        expect(data_get($selected->firstOrFail()->refresh()->containers, 'main.widgets'))->toBe([$placements[0]]);
    }

    expect(data_get($excluded->refresh()->containers, 'main.widgets'))->toBe($placements);
})->with(['one layout' => [1], 'queue threshold' => [50]]);

it('refuses a bulk edit after its preview has been removed', function (): void {
    $this->actingAsAdmin();
    $widget = Widget::factory()->create();
    $placement = ['widget_key' => $widget->key, 'occurrence' => 1, 'container' => 'main'];
    $layout = Layout::factory()->create(['status' => true, 'containers' => ['main' => ['widgets' => [$placement]]]]);

    $component = Livewire::test(ListLayouts::class)
        ->mountAction(TestAction::make('bulkChangeLayouts')->table())
        ->fillForm([
            'layout_keys' => [$layout->key],
            'operation_type' => LayoutBulkWidgetOperationType::RemoveWidget->value,
            'source_widget_key' => $widget->key,
            'source_container_key' => 'main',
        ]);

    advanceLayoutBulkChangeWizard($component);

    LayoutBulkChangeRun::query()->sole()->delete();

    $component->callMountedAction()
        ->assertActionHalted()
        ->assertNotified(__('capell-layout-builder::message.bulk_change_preview_missing'));

    expect(data_get($layout->refresh()->containers, 'main.widgets'))->toBe([$placement]);
});

it('keeps the layout and default assets intact when the preview blocks an ambiguous move', function (): void {
    $this->actingAsAdmin();
    $widget = Widget::factory()->create();
    $containers = [
        'main' => ['widgets' => [['widget_key' => $widget->key, 'container' => 'main', 'occurrence' => 1]]],
        'sidebar' => ['widgets' => [['widget_key' => $widget->key, 'container' => 'sidebar', 'occurrence' => 1]]],
    ];
    $layout = Layout::factory()->create(['status' => true, 'containers' => $containers]);
    $asset = WidgetAsset::factory()->widget($widget)->container('main')->occurrence(1)->create([
        'pageable_type' => null,
        'pageable_id' => null,
    ]);

    $component = Livewire::test(ListLayouts::class)
        ->mountAction(TestAction::make('bulkChangeLayouts')->table())
        ->fillForm([
            'layout_keys' => [$layout->key],
            'operation_type' => LayoutBulkWidgetOperationType::MoveWidgetToContainer->value,
            'source_widget_key' => $widget->key,
            'source_container_key' => 'main',
            'target_container_key' => 'sidebar',
            'placement' => 'bottom',
            'occurrence_mode' => 'first',
        ]);

    advanceLayoutBulkChangeWizard($component);

    $run = LayoutBulkChangeRun::query()->sole();
    expect($run->status)->toBe(LayoutBulkChangeRunStatus::Blocked);
    $component->callMountedAction()->assertActionHalted()->assertNotified();

    expect($run->refresh()->status)->toBe(LayoutBulkChangeRunStatus::Blocked)
        ->and($layout->refresh()->containers)->toBe($containers)
        ->and($asset->refresh()->container)->toBe('main');
});

function advanceLayoutBulkChangeWizard(Testable $component): void
{
    $instance = $component->instance();
    assert($instance instanceof ListLayouts);
    $schemaName = $instance->getMountedActionSchemaName();
    assert(is_string($schemaName));
    $schema = $instance->getSchema($schemaName);
    assert($schema !== null);

    foreach ($schema->getFlatComponents() as $wizard) {
        if ($wizard instanceof Wizard) {
            $key = $wizard->getKey();
            assert(is_string($key));
            $component->call('callSchemaComponentMethod', $key, 'nextStep', [0])->assertHasNoFormErrors();

            return;
        }
    }

    test()->fail('Expected the bulk-change wizard on the mounted action.');
}
