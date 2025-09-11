<?php

declare(strict_types=1);

namespace Capell\Layout\Livewire\Filament;

use Capell\Admin\Actions\BuildWidgetAssetDataAction;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Components\Tables\Actions\CreateAction;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Core\Data\AssetData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Layout\Enums\SchemaTypeEnum;
use Capell\Layout\Enums\WidgetAssetSchemaEnum;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;
use Livewire\Attributes\On;
use Livewire\Component;

#[On('refresh')]
class WidgetAssetsTable extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public ?Widget $record = null;

    private ?Schema $schema = null;

    private bool $withHeading = true;

    public function mount(Schema $schema, bool $withHeading = true): void
    {
        throw_if(
            ! Filament::auth()->check(),
            AuthenticationException::class
        );

        if (! $this->record instanceof Widget) {
            throw new InvalidArgumentException('The record must be an instance of Widget.');
        }

        $this->schema = $schema;

        $this->withHeading = $withHeading;
    }

    public function table(Table $table): Table
    {
        return $table->query(
            fn (): Builder => $this->getRelationship()
                ->getQuery()
                ->select([
                    $this->getRelationship()->getRelated()->getTable() . '.*',
                    DB::raw(self::getAssetNameSql() . ' as asset_name'),
                ])
                ->when(
                    CapellCore::getAssets(),
                    function (Builder $query, $assets): Builder {
                        foreach ($assets as $asset) {
                            $model = app($asset->model);
                            $relatedTable = $this->getRelationship()->getRelated()->getTable();
                            $query->leftJoin(
                                $model->getTable(),
                                fn (JoinClause $join): JoinClause => $join->on(
                                    $relatedTable . '.asset_id',
                                    '=',
                                    $model->getTable() . '.id'
                                )
                                    ->where($relatedTable . '.asset_type', '=', $model->getMorphClass())
                            );
                        }

                        return $query;
                    }
                )
        )
            ->columns([
                NameColumn::make('asset_name')
                    ->sortable(
                        query: fn (Builder $query, string $direction): Builder => $query->orderByRaw(
                            $this->getAssetNameSql() . $direction
                        ),
                    ),
                IconColumn::make('asset_type')
                    ->label(__('capell-admin::table.type'))
                    ->sortable()
                    ->alignCenter()
                    ->getStateUsing(fn (WidgetAsset $record): \Capell\Core\Data\AssetData => CapellCore::getAsset($record->asset_type))
                    ->icon(fn (?AssetData $state): ?string => $state?->getIcon())
                    ->tooltip(fn (?AssetData $state): ?string => $state?->getLabel()),
            ])
            ->when(
                $this->withHeading,
                fn (Table $table): Table => $table->heading(__('capell-admin::generic.assets'))
                    ->description(__('capell-admin::generic.widget_assets_description'))
            )
            ->headerActions(
                collect(CapellCore::getAssets())
                    ->map(
                        fn (AssetData $asset): ActionGroup => ActionGroup::make([
                            $this->selectAssetAction($this->record, $asset),
                            $this->addAssetAction($this->record, $asset),
                        ])
                            ->label(__('capell-admin::button.add_asset_type', ['type' => $asset->getLabel()]))
                            ->icon($asset->getIcon())
                            ->button()
                            ->dropdownPlacement('bottom-end'),
                    )
                    ->all()
            )
            ->recordActions([
                ActionGroup::make([
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    #[On('sync-selected-assets')]
    public function syncSelectedAssets(array $arguments, string $type, array $assets): void
    {
        foreach ($assets as $assetId) {
            $this->record->assets()->create([
                'asset_type' => match ($type) {
                    'page' => app(Page::class)->getMorphClass(),
                    'content' => app(Content::class)->getMorphClass(),
                },
                'asset_id' => $assetId,
            ]);
        }
    }

    public function render(): string
    {
        return <<<'blade'
            <div wire:ignore.self>
                {{ $this->table }}
            </div>
        blade;
    }

    /**
     * @return HasMany
     */
    protected function getRelationship(): Relation
    {
        return $this->record->assets();
    }

    private function addAssetAction(?Widget $widget, AssetData $asset): Action
    {
        return CreateAction::make()
            ->label(__('capell-admin::button.add_new_asset'))
            ->icon('heroicon-o-plus-circle')
            ->slideOver()
            ->modal()
            ->modalWidth(Width::SixExtraLarge)
            ->closeModalByClickingAway(false)
            ->modalHeading(
                fn (self $livewire): string => __(
                    'capell-admin::generic.add_widget_asset',
                    [
                        'widget' => $widget->name,
                        'asset' => $asset->getLabel(),
                    ]
                )
            )
            ->modalSubmitActionLabel(
                fn (Action $action): string => __(
                    'capell-admin::button.create_widget_asset',
                    ['type' => $asset->getLabel()]
                )
            )
            ->successNotificationTitle(__('capell-admin::message.asset_added'))
            ->model($asset->model)
            ->schema(
                fn (Schema $schema): Schema => $schema->schema(
                    $this->getWidgetAssetSchema($widget, $asset->name, $schema)
                )
            )
            ->fillForm(fn (self $livewire): array => BuildWidgetAssetDataAction::run($asset->name))
            ->after(function (Model $record) use ($widget): void {
                $widget->assets()->create([
                    'asset_type' => $record->getMorphClass(),
                    'asset_id' => $record->id,
                ]);
            });
    }

    private function selectAssetAction(?Widget $record, AssetData $asset): Action
    {
        return Action::make('associate_asset_' . $asset->name)
            ->icon('heroicon-o-magnifying-glass')
            ->label(__('capell-admin::button.select_existing'))
            ->extraModalWindowAttributes([
                'class' => 'capell-layout-builder-assets-table',
            ])
            ->modal()
            ->modalWidth(Width::SixExtraLarge)
            ->modalContent(function (Action $action) use ($record, $asset): HtmlString {
                /** @var self $livewire */
                $livewire = $action->getLivewire();

                $componentName = 'capell-layout::livewire.assets.table.' . strtolower($asset->name);

                $existingRecords = $record->assets()
                    ->where('asset_type', $asset->name)
                    ->pluck('asset_id')
                    ->toArray();

                return new HtmlString(Blade::render(<<<'blade'
                <livewire:is
                    :$actionModalId
                    :component="$componentName"
                    :$existingRecords
                 />
            blade, [
                    'actionModalId' => $livewire->getId() . '-associate-action-' . $asset->name,
                    'componentName' => $componentName,
                    'existingRecords' => $existingRecords,
                ]));
            })
            ->submit(null)
            ->modalSubmitAction(false)
            ->modalCancelAction(false);
    }

    private function getWidgetAssetSchema(?Widget $record, string $assetType, Schema $schema): array
    {
        $type = $record->admin['widget_asset_schema'][$assetType]
            ?? $record->type->admin['widget_asset_schema'][$assetType]
            ?? null;

        if ($type) {
            $typeSchema = CapellAdmin::getSchema(SchemaTypeEnum::WidgetAsset->value, $type);

            return app($typeSchema)::make($schema);
        }

        $typeSchema = WidgetAssetSchemaEnum::fromName(ucfirst($assetType))->value;

        return app($typeSchema)::make($schema);
    }

    private function getAssetNameSql(): string
    {
        return 'CASE ' .
            collect(CapellCore::getAssets())
                ->map(function ($asset): string {
                    $model = app($asset->model);
                    $relatedTable = $this->getRelationship()->getRelated()->getTable();

                    return sprintf("WHEN %s.asset_type = '%s' THEN %s.name", $relatedTable, $model->getMorphClass(), $model->getTable());
                })
                ->implode(' ') .
            ' ELSE widget_assets.asset_type END ';
    }
}
