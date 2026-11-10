<?php

declare(strict_types=1);

use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Configurators\Pages\DefaultPageConfigurator;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Translation;
use Capell\LayoutBuilder\Filament\Components\Forms\AssetsRepeater;
use Capell\LayoutBuilder\Filament\Components\Forms\WidgetSelect;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Tests\Support\WidgetOptionSchemaHarness;
use Capell\Tests\Fixtures\Models\User;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('binds widget option forms to their mounted action state', function (string $operation): void {
    $widget = Widget::factory()->create(['name' => 'Existing widget']);
    $component = new WidgetOptionSchemaHarness;
    $component->data = ['widget_id' => $widget->getKey()];
    $select = WidgetSelect::make('widget_id')->container(Schema::make($component)->statePath('data'));
    $schema = Schema::make($component)->statePath('mountedActions.0.data')->operation($operation)->model($widget);
    $optionForm = $operation === 'createOption'
        ? $select->withCreateForm()->getCreateOptionActionForm($schema)
        : $select->withEditForm()->getEditOptionActionForm($schema);

    expect($optionForm)->toBeInstanceOf(Schema::class);
    if (! $optionForm instanceof Schema) {
        throw new RuntimeException('Expected option schema.');
    }
    $optionForm->fill(['name' => 'Widget draft']);
    expect(data_get($component->mountedActions, '0.data.name'))->toBe('Widget draft');
})->with(['createOption', 'editOption']);

it('saves widget relationships from the create option form', function (): void {
    $language = Language::factory()->create();
    $blueprint = Blueprint::factory()->create(['type' => 'widget']);
    $component = new WidgetOptionSchemaHarness;
    $schema = Schema::make($component)->statePath('mountedActions.0.data')->model(Widget::class)->components([
        Repeater::make('translations')->relationship()->schema([
            Hidden::make('language_id'),
            TextInput::make('title'),
        ]),
    ]);
    $schema->fill(['translations' => [['language_id' => $language->getKey(), 'title' => 'Widget translation']]]);
    $select = WidgetSelect::make('widget_id')->withCreateForm();
    $widgetId = $select->evaluate($select->getCreateOptionUsing(), [
        'component' => $select,
        'data' => ['name' => 'Created widget', 'key' => 'created-widget', 'blueprint_id' => $blueprint->getKey()],
        'schema' => $schema,
        'form' => $schema,
    ]);

    $widget = Widget::query()->whereKey($widgetId)->firstOrFail();
    expect($widget->name)->toBe('Created widget')
        ->and($widget->translations()->where('language_id', $language->getKey())->value('title'))->toBe('Widget translation');
});

it('rolls back a widget when saving its option relationships fails', function (): void {
    $language = Language::factory()->create();
    $blueprint = Blueprint::factory()->create(['type' => 'widget']);
    $component = new WidgetOptionSchemaHarness;
    $schema = Schema::make($component)->statePath('mountedActions.0.data')->model(Widget::class)->components([
        Repeater::make('translations')->relationship()->schema([
            Hidden::make('language_id'),
            TextInput::make('title'),
        ]),
    ]);
    $schema->fill(['translations' => [['language_id' => $language->getKey(), 'title' => 'Widget translation']]]);
    $select = WidgetSelect::make('widget_id')->container(Schema::make($component)->statePath('data'))->withCreateForm();
    $action = $select->getCreateOptionAction();
    if (! $action instanceof Action) {
        throw new RuntimeException('Expected create option action.');
    }
    Event::listen('eloquent.creating: ' . Translation::class, static function (): never {
        throw new RuntimeException('Relationship save failed');
    });

    $action->beginDatabaseTransaction();
    try {
        $select->evaluate($select->getCreateOptionUsing(), [
            'component' => $select,
            'data' => ['name' => 'Partial widget', 'key' => 'partial-widget', 'blueprint_id' => $blueprint->getKey()],
            'schema' => $schema,
            'form' => $schema,
        ]);
        $action->commitDatabaseTransaction();
        test()->fail('Expected relationship save to fail.');
    } catch (RuntimeException $exception) {
        $action->rollBackDatabaseTransaction();
        expect($exception->getMessage())->toBe('Relationship save failed');
    }

    expect(Widget::query()->where('key', 'partial-widget')->exists())->toBeFalse();
});

it('binds asset creation forms to action state and uses a transaction', function (string $repeaterClass): void {
    CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::configurator(
        class: DefaultPageConfigurator::class,
        group: 'Pages',
        name: 'default',
    ));
    Blueprint::factory()->page()->default()->create();
    $component = new WidgetOptionSchemaHarness;
    $component->data = ['asset_type' => 'page'];
    $fields = (new ReflectionMethod($repeaterClass, 'getFormSchema'))->invoke(null);
    if (! is_array($fields) || ! ($fields[1] ?? null) instanceof Select) {
        throw new RuntimeException('Expected asset select in the repeater schema.');
    }
    $select = $fields[1];
    $select->container(Schema::make($component)->statePath('data'));
    $schema = Schema::make($component)->statePath('mountedActions.0.data')->operation('createOption');
    $optionForm = $select->getCreateOptionActionForm($schema);

    expect($optionForm)->toBeInstanceOf(Schema::class);
    if (! $optionForm instanceof Schema) {
        throw new RuntimeException('Expected option schema.');
    }
    $optionForm->fill(['name' => 'Page draft']);
    expect(data_get($component->mountedActions, '0.data.name'))->toBe('Page draft')
        ->and($select->getCreateOptionAction()?->hasDatabaseTransactions())->toBeTrue();
})->with([
    'widget assets' => AssetsRepeater::class,
]);

it('rolls back an asset when its option relationship save fails', function (): void {
    $language = Language::factory()->create();
    $component = new WidgetOptionSchemaHarness;
    $component->data = ['asset_type' => 'page'];
    $fields = (new ReflectionMethod(AssetsRepeater::class, 'getFormSchema'))->invoke(null);
    if (! is_array($fields) || ! ($fields[1] ?? null) instanceof Select) {
        throw new RuntimeException('Expected asset select in the repeater schema.');
    }
    $select = $fields[1];
    $select->container(Schema::make($component)->statePath('data'));
    $schema = Schema::make($component)->statePath('mountedActions.0.data')->model(Page::class)->components([
        Repeater::make('translations')->relationship()->schema([
            Hidden::make('language_id'),
            TextInput::make('title'),
        ]),
    ]);
    $schema->fill(['translations' => [['language_id' => $language->getKey(), 'title' => 'Page translation']]]);
    $page = Page::factory()->make(['name' => 'Partial asset']);
    $action = $select->getCreateOptionAction();
    if (! $action instanceof Action) {
        throw new RuntimeException('Expected create option action.');
    }
    Event::listen('eloquent.creating: ' . Translation::class, static function (): never {
        throw new RuntimeException('Asset relationship save failed');
    });

    $action->beginDatabaseTransaction();
    try {
        $select->evaluate($select->getCreateOptionUsing(), [
            'component' => $select,
            'data' => Arr::except($page->getAttributes(), ['created_at', 'updated_at']),
            'schema' => $schema,
            'form' => $schema,
        ]);
        $action->commitDatabaseTransaction();
        test()->fail('Expected asset relationship save to fail.');
    } catch (RuntimeException $exception) {
        $action->rollBackDatabaseTransaction();
        expect($exception->getMessage())->toBe('Asset relationship save failed');
    }

    expect(Page::query()->where('name', 'Partial asset')->exists())->toBeFalse();
});

it('rejects asset option creation when the actor cannot create that asset', function (): void {
    $component = new WidgetOptionSchemaHarness;
    $component->data = ['asset_type' => 'page'];
    $fields = (new ReflectionMethod(AssetsRepeater::class, 'getFormSchema'))->invoke(null);
    if (! is_array($fields) || ! ($fields[1] ?? null) instanceof Select) {
        throw new RuntimeException('Expected asset select in the repeater schema.');
    }
    $select = $fields[1];
    $select->container(Schema::make($component)->statePath('data'));
    $schema = Schema::make($component)->statePath('mountedActions.0.data')->model(Page::class);
    $page = Page::factory()->make(['name' => 'Denied asset option']);
    test()->actingAs(User::factory()->create());
    Gate::before(static fn ($user, string $ability, array $arguments): ?bool => $ability === 'create' && ($arguments[0] ?? null) === Page::class ? false : null);

    expect(fn (): mixed => $select->evaluate($select->getCreateOptionUsing(), [
        'component' => $select,
        'data' => Arr::except($page->getAttributes(), ['created_at', 'updated_at']),
        'schema' => $schema,
        'form' => $schema,
    ]))->toThrow(AuthorizationException::class);
    expect(Page::query()->where('name', 'Denied asset option')->exists())->toBeFalse();
});

it('rejects widget option creation when the actor cannot create widgets', function (): void {
    $component = new WidgetOptionSchemaHarness;
    $select = WidgetSelect::make('widget_id')->withCreateForm();
    $schema = Schema::make($component)->statePath('mountedActions.0.data')->model(Widget::class);
    $blueprint = Blueprint::factory()->create(['type' => 'widget']);
    test()->actingAs(User::factory()->create());
    Gate::before(static fn ($user, string $ability, array $arguments): ?bool => $ability === 'create' && ($arguments[0] ?? null) === Widget::class ? false : null);

    expect(fn (): mixed => $select->evaluate($select->getCreateOptionUsing(), [
        'component' => $select,
        'data' => ['name' => 'Denied widget', 'key' => 'denied-widget', 'blueprint_id' => $blueprint->getKey()],
        'schema' => $schema,
        'form' => $schema,
    ]))->toThrow(AuthorizationException::class);
    expect(Widget::query()->where('key', 'denied-widget')->exists())->toBeFalse();
});
