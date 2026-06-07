<?php

declare(strict_types=1);

use Capell\Core\Data\Makers\MakerInputData;
use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Actions\InstallPackageAction;
use Capell\LayoutBuilder\Actions\MakeWidgetAction;
use Capell\LayoutBuilder\Actions\SaveFormComponentRelationshipAction;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Enums\WidgetTypeEnum;
use Capell\LayoutBuilder\Filament\Components\Forms\Layout\LayoutTab;
use Capell\LayoutBuilder\Filament\Resources\Widgets\RelationManagers\LayoutsRelationManager;
use Capell\LayoutBuilder\Support\Makers\LayoutBuilderWidgetMaker;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderAssetsRepeaterHarness;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderCoverageSchemaHarness;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Contracts\CanEntangleWithSingularRelationships;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

it('scaffolds widget blade and livewire files with registration guidance', function (): void {
    $name = 'Coverage Spotlight ' . str_replace('.', '', uniqid('', true));
    $kebab = str($name)->studly()->kebab()->toString();
    $viewDirectory = sys_get_temp_dir() . '/capell-layout-builder-widgets-' . $kebab;

    $result = MakeWidgetAction::run($name, $viewDirectory, livewire: true, force: true);

    $classPath = app_path('Livewire/Widgets/' . str($name)->studly()->toString() . 'Widget.php');
    $livewireViewPath = resource_path('views/widgets/livewire/' . $kebab . '.blade.php');

    expect($result->created)->toBeTrue()
        ->and($result->viewPath)->toBe($viewDirectory . DIRECTORY_SEPARATOR . $kebab . '.blade.php')
        ->and(file_get_contents($result->viewPath))->toContain($kebab)
        ->and(file_exists($classPath))->toBeTrue()
        ->and(file_exists($livewireViewPath))->toBeTrue()
        ->and($result->seederSnippet)->toContain(sprintf("['type' => 'widget', 'key' => '%s']", $kebab));

    @unlink($result->viewPath);
    @rmdir($viewDirectory);
    @unlink($classPath);
    @unlink($livewireViewPath);
});

it('previews and runs the layout builder widget maker with livewire files', function (): void {
    $input = new MakerInputData(
        maker: 'layout-builder.widget',
        values: ['name' => 'Maker Coverage Card', 'livewire' => true],
        dryRun: false,
        force: true,
        databaseWrites: false,
    );

    $maker = new LayoutBuilderWidgetMaker;
    $definition = $maker->definition();
    $preview = $maker->preview($input);
    $result = $maker->run($input);

    expect($definition->key)->toBe('layout-builder.widget')
        ->and($preview->files)->toHaveCount(3)
        ->and($preview->commands->first())->toContain('capell:layout-builder-make-widget')
        ->and($result->successful)->toBeTrue()
        ->and($result->files)->toHaveCount(3)
        ->and($result->notes->first())->toContain('maker-coverage-card');
});

it('keeps the compatibility install action wired for consuming package setup helpers', function (): void {
    InstallPackageAction::run();

    expect(Layout::query()->where('key', LayoutEnum::Default->value)->exists())->toBeTrue()
        ->and(Blueprint::query()
            ->where('type', LayoutTypeEnum::Widget->value)
            ->where('key', WidgetTypeEnum::Hero->value)
            ->exists())->toBeTrue();
});

it('builds the widget layouts relation manager table and layout tab schema', function (): void {
    $layout = Layout::factory()->create();
    $relationManager = new LayoutsRelationManager;
    $table = $relationManager->table(layoutBuilderCoverageTable());

    $tab = LayoutTab::make('layout');
    $childComponents = layoutBuilderCoverageTabComponents($tab, $layout);

    expect(LayoutsRelationManager::getTitle($layout, 'edit'))->toBe(__('capell-admin::generic.layouts'))
        ->and(array_keys($table->getColumns()))->toContain('name', 'admin.image', 'site.name', 'theme.name', 'pages_count', 'status', 'created_at', 'updated_at')
        ->and(array_keys($table->getFilters()))->toBe(['site_id'])
        ->and($childComponents)->toHaveCount(1)
        ->and($childComponents[0])->toBeInstanceOf(Livewire::class);
});

it('saves and removes singular relationship records through the shared form relationship action', function (): void {
    $layout = Layout::factory()->create(['name' => 'Original Layout']);
    $schema = Mockery::mock(Schema::class);
    $schema->shouldReceive('getState')->andReturn(['name' => 'Updated Layout']);

    $updateComponent = Mockery::mock(Component::class, CanEntangleWithSingularRelationships::class);
    $updateComponent->shouldReceive('getCachedExistingRecord')->andReturn($layout);
    $updateComponent->shouldReceive('hasRelationship')->andReturnTrue();
    $updateComponent->shouldReceive('getChildSchema')->andReturn($schema);
    $updateComponent->shouldReceive('mutateRelationshipDataBeforeSave')->with(['name' => 'Updated Layout'])->andReturn(['name' => 'Updated Layout']);
    $updateComponent->shouldReceive('cachedExistingRecord')->with($layout)->once();

    SaveFormComponentRelationshipAction::run($updateComponent, new LayoutBuilderCoverageSchemaHarness);

    expect($layout->refresh()->name)->toBe('Updated Layout');

    $deleteComponent = Mockery::mock(Component::class, CanEntangleWithSingularRelationships::class);
    $deleteComponent->shouldReceive('getCachedExistingRecord')->andReturn($layout);
    $deleteComponent->shouldReceive('hasRelationship')->andReturnFalse();

    SaveFormComponentRelationshipAction::run($deleteComponent, new LayoutBuilderCoverageSchemaHarness);

    expect(Layout::query()->whereKey($layout)->exists())->toBeFalse();
});

it('adds layout builder widget assets through the repeater action workflow', function (): void {
    $assetPage = Page::factory()->create(['name' => 'Layout builder asset page']);
    $component = LayoutBuilderAssetsRepeaterHarness::make('assets');
    $component->container(Schema::make(new LayoutBuilderCoverageSchemaHarness)->operation('edit'));
    $component->generateUuidUsing(static fn (): string => 'layout-asset-row');

    $component->getAddAssetAction()->call([
        'component' => $component,
        'arguments' => [
            'asset_type' => 'page',
            'asset_id' => $assetPage->getKey(),
        ],
    ]);

    $editAction = $component->getExtraItemActions()['edit_asset'];
    $editAction
        ->schemaComponent($component)
        ->arguments(['item' => 'layout-asset-row']);

    expect($component->rawState)->toHaveKey('layout-asset-row')
        ->and($component->rawState['layout-asset-row'])->toMatchArray([
            'asset_type' => 'page',
            'asset_id' => $assetPage->getKey(),
        ])
        ->and($component->lastChildSchema)->toBeInstanceOf(Schema::class)
        ->and($component->afterStateUpdatedCalled)->toBeTrue()
        ->and($component->partiallyRendered)->toBeTrue()
        ->and($component->collapsedCalled)->toBeTrue()
        ->and($editAction->isVisible())->toBeTrue()
        ->and($editAction->getTooltip())->toContain('page');
});

function layoutBuilderCoverageTable(): Table
{
    $livewire = Mockery::mock(HasTable::class);
    $livewire->shouldIgnoreMissing();
    $livewire->shouldReceive('makeFilamentTranslatableContentDriver')->andReturn(null)->byDefault();
    $livewire->shouldReceive('getTableFilterState')->andReturn([])->byDefault();
    $livewire->shouldReceive('isTableLoaded')->andReturnTrue()->byDefault();
    $livewire->shouldReceive('getTableArguments')->andReturn([])->byDefault();

    return Table::make($livewire);
}

/**
 * @return array<int, mixed>
 */
function layoutBuilderCoverageTabComponents(LayoutTab $tab, Layout $layout): array
{
    $property = new ReflectionProperty(LayoutTab::class, 'childComponents');
    $childComponents = $property->getValue($tab);
    $schema = $childComponents['default'] ?? null;

    if (! $schema instanceof Closure) {
        return [];
    }

    return $tab->evaluate($schema, ['record' => $layout]);
}
