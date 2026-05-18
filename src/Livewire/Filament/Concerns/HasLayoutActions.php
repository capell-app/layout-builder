<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Livewire\Filament\Concerns;

use Capell\Admin\Actions\ReplicateLayoutAction;
use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Exceptions\MissingElementAssetException;
use Capell\LayoutBuilder\Filament\Resources\Elements\Schemas\ElementAssetForm;
use Capell\LayoutBuilder\Livewire\Filament\Actions\LayoutBuilderActionFactory;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Models\ElementAsset;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\HtmlString;

trait HasLayoutActions
{
    public function saveLayoutAction(): Action
    {
        return $this->layoutBuilderActionFactory()->saveLayoutAction();
    }

    public function duplicateLayoutAction(): Action
    {
        return $this->layoutBuilderActionFactory()->duplicateLayoutAction();
    }

    public function cloneLayoutForPageAction(): Action
    {
        return $this->layoutBuilderActionFactory()->cloneLayoutForPageAction();
    }

    public function undoLayoutMutationAction(): Action
    {
        return $this->layoutBuilderActionFactory()->undoLayoutMutationAction();
    }

    public function redoLayoutMutationAction(): Action
    {
        return $this->layoutBuilderActionFactory()->redoLayoutMutationAction();
    }

    public function addContainerAction(): Action
    {
        return $this->layoutBuilderActionFactory()->addContainerAction();
    }

    public function editContainerAction(): Action
    {
        return $this->layoutBuilderActionFactory()->editContainerAction();
    }

    public function removeContainerAction(): Action
    {
        return $this->layoutBuilderActionFactory()->removeContainerAction();
    }

    public function moveContainerUpAction(): Action
    {
        return $this->layoutBuilderActionFactory()->moveContainerUpAction();
    }

    public function moveContainerDownAction(): Action
    {
        return $this->layoutBuilderActionFactory()->moveContainerDownAction();
    }

    public function duplicateContainerAction(): Action
    {
        return $this->layoutBuilderActionFactory()->duplicateContainerAction();
    }

    public function editLayoutElementAction(): Action
    {
        return $this->layoutBuilderActionFactory()->editLayoutElementAction();
    }

    public function addElementAction(): Action
    {
        return $this->layoutBuilderActionFactory()->addElementAction();
    }

    public function editElementAction(): Action
    {
        return $this->layoutBuilderActionFactory()->editElementAction();
    }

    public function duplicateElementAction(): Action
    {
        return $this->layoutBuilderActionFactory()->duplicateElementAction();
    }

    public function moveElementUpAction(): Action
    {
        return $this->layoutBuilderActionFactory()->moveElementUpAction();
    }

    public function moveElementDownAction(): Action
    {
        return $this->layoutBuilderActionFactory()->moveElementDownAction();
    }

    public function moveElementToContainerAction(): Action
    {
        return $this->layoutBuilderActionFactory()->moveElementToContainerAction();
    }

    public function removeElementAction(): Action
    {
        return $this->layoutBuilderActionFactory()->removeElementAction();
    }

    public function selectAssetAction(): Action
    {
        return $this->layoutBuilderActionFactory()->selectAssetAction();
    }

    public function addAssetAction(): Action
    {
        return $this->layoutBuilderActionFactory()->addAssetAction();
    }

    public function editElementAssetAction(): Action
    {
        return $this->layoutBuilderActionFactory()->editElementAssetAction();
    }

    public function moveAssetUpAction(): Action
    {
        return $this->layoutBuilderActionFactory()->moveAssetUpAction();
    }

    public function moveAssetDownAction(): Action
    {
        return $this->layoutBuilderActionFactory()->moveAssetDownAction();
    }

    public function removeAssetsAction(): Action
    {
        return $this->layoutBuilderActionFactory()->removeAssetsAction();
    }

    public function changeLayoutAction(): Action
    {
        return $this->layoutBuilderActionFactory()->changeLayoutAction();
    }

    public function togglePageAssetsAction(): Action
    {
        return $this->layoutBuilderActionFactory()->togglePageAssetsAction();
    }

    protected function addAssetFromAction(Action $action, array $arguments, array $data): void
    {
        $this->assertCanUpdateLayout();

        $this->loadFromStore();

        $configurator = $this->getMountedActionSchema();

        throw_unless($configurator instanceof Schema, Exception::class, 'Mounted action schema not found.');

        $configurator->livewire($this);

        $containerKey = $arguments['containerKey'];
        $elementIndex = $arguments['elementIndex'];
        $type = $arguments['type'];

        $hasPageAssets = $this->shouldAddPageAssets($containerKey, $elementIndex);

        $element = $this->getContainerElement($containerKey, $elementIndex);

        $order = $this->countElementAssets($containerKey, $elementIndex) + 1;

        /** @var ElementAsset $elementAsset */
        $elementAsset = $configurator->getRecord();

        // Fake exists to ensure assets relations are saved correctly
        $elementAsset->exists = true;
        $elementAsset->wasRecentlyCreated = true; // prevent MissingAttributeException

        $data['layout_element_id'] = $element->id;

        // Ensure UpdatedModelAction is not triggered
        ElementAsset::withoutEvents(function () use ($configurator): void {
            $configurator->saveRelationships();
        });

        if (! isset($this->assets[$containerKey][$elementIndex])) {
            $this->assets[$containerKey][$elementIndex] = [];
        }

        $assetId = $elementAsset->asset_id;

        $element = $this->getContainerElement($containerKey, $elementIndex);

        $occurrence = $this->getContainerElementOccurrence($containerKey, $elementIndex);

        $meta = $data[$assetId] ?? [];

        $asset = [
            'asset_id' => $assetId,
            'asset_type' => $type,
            'meta' => $meta,
            'layout_element_id' => $element->id,
            'order' => $order,
            'occurrence' => $occurrence,
        ];

        if ($hasPageAssets) {
            $asset['pageable_id'] = $this->page->getKey();
            $asset['pageable_type'] = $this->page->getMorphClass();
            $asset['container'] = $containerKey;
        }

        $this->assets[$containerKey][$elementIndex][] = $asset;

        $elementAsset->load([
            'asset' => fn (MorphTo $query): MorphTo => $query->morphWith($this->getAssetRelations()),
        ]);

        $elementAsset->setRelation('element', $element);

        $element->assets->add($elementAsset);

        $this->layoutUpdated();

        $action->success();

        $this->dispatch(
            'refresh-assets',
            containerKey: $containerKey,
            elementIndex: $elementIndex,
        );
    }

    protected function duplicateLayout(): void
    {
        $this->assertCanUpdateLayout();

        $newLayout = ReplicateLayoutAction::run($this->layout);

        $this->dispatch('page-layout-changed', id: $newLayout->getKey());
    }

    protected function changePageLayout(int $layoutId): void
    {
        $this->assertCanUpdateLayout();

        if (! $this->inPageContext()) {
            return;
        }

        $this->layoutUpdated();

        $this->dispatch('page-layout-changed', id: $layoutId);
    }

    protected function makeElementAssetRecordForCreate(array $arguments): ElementAsset
    {
        $containerKey = $arguments['containerKey'];
        $elementIndex = $arguments['elementIndex'];
        $assetType = $arguments['type'];

        $element = $this->getContainerElement($containerKey, $elementIndex);

        /** @var class-string<ElementAsset> $model */
        $model = ElementAsset::class;

        $record = $model::query()->make([
            'layout_element_id' => $element->id,
            'workspace_id' => $this->getCurrentElementAssetWorkspaceId($element),
            'asset_type' => $assetType,
            'meta' => [],
        ]);

        $asset = CapellCore::getAsset($assetType)->model::make();

        $record->setRelation('asset', $asset);

        return $record;
    }

    protected function resolveEditableElementAsset(array $arguments): ElementAsset
    {
        $containerKey = $arguments['containerKey'];
        $elementIndex = $arguments['elementIndex'];
        $index = $arguments['index'];
        $type = $arguments['type'];

        $element = $this->getContainerElement($containerKey, $elementIndex);
        $asset = $this->getElementAsset($containerKey, $elementIndex, $index);

        throw_unless($asset, MissingElementAssetException::class, $element, $type, $index, $arguments);

        $assetId = $asset['asset_id'];

        $elementAsset = isset($asset['id'])
            ? $element->assets->first(fn (ElementAsset $elementAsset): bool => (int) $elementAsset->getKey() === (int) $asset['id'])
            : null;

        $elementAsset ??= $element->assets
            ->where('asset_type', $type)
            ->where('asset_id', $assetId)
            ->first();

        throw_unless($elementAsset, Exception::class, sprintf('Asset of type [%s] with ID [%s] not found.', $type, $assetId));
        throw_unless((int) $elementAsset->getAttribute('layout_element_id') === (int) $element->getKey(), Exception::class, sprintf('Asset of type [%s] with ID [%s] is not attached to this element.', $type, $assetId));

        return $elementAsset;
    }

    protected function getEditElementAssetModalHeading(self $livewire, array $arguments): string
    {
        $name = str($arguments['type'])->title();

        if ($livewire->inPageContext()) {
            return __('capell-layout-builder::heading.edit_page_element_asset', ['name' => $name]);
        }

        return __('capell-layout-builder::heading.edit_element_asset', ['name' => $name]);
    }

    protected function getEditElementAssetModalDescription(self $livewire, array $arguments): ?string
    {
        if (! $livewire->inPageContext()) {
            return null;
        }

        $elementAsset = $this->getElementAsset($arguments['containerKey'], $arguments['elementIndex'], $arguments['index']);

        if (! isset($elementAsset['pageable_id'], $elementAsset['pageable_type'])) {
            return null;
        }

        return __('capell-layout-builder::heading.page_element_asset', ['name' => $livewire->page->name]);
    }

    protected function applyElementAssetUpdate(ElementAsset $record, array $data, self $livewire, array $arguments, Action $action, Schema $configurator): void
    {
        $this->assertCanEditContent();

        $this->loadFromStore();

        $expectedSignature = $arguments['contentInventorySignature'] ?? null;

        if (is_string($expectedSignature) && ! hash_equals($expectedSignature, $this->contentInventorySignature())) {
            Notification::make('content-inventory-stale')
                ->title(__('capell-layout-builder::message.content_stale'))
                ->warning()
                ->send();

            $action->halt();
        }

        $element = $this->getContainerElement($arguments['containerKey'], $arguments['elementIndex']);
        $canUpdatePersistedRecord = $record->workspace_id === $this->getCurrentElementAssetWorkspaceId($element);

        if ($canUpdatePersistedRecord) {
            $configurator->saveRelationships();
        }

        if ($data !== [] && $canUpdatePersistedRecord) {
            $record->update($data);
        }

        if (isset($data['meta'])) {
            $livewire->updateElementAssetContentState($arguments['containerKey'], $arguments['elementIndex'], $arguments['index'], ['meta' => $data['meta']]);
        }

        $livewire->reloadContainerElementAsset($arguments['containerKey'], $arguments['elementIndex'], $arguments['index']);

        $action->success();
    }

    protected function getElementAssetSchema(Schema $configurator): Schema
    {
        return ElementAssetForm::configure($configurator);
    }

    protected function getChangeLayoutSchema(): array
    {
        return [
            Select::make('layout_id')
                ->label(__('capell-admin::form.layout'))
                ->required()
                ->searchable()
                ->options(
                    fn (): array => Layout::query()
                        ->withCount('pages')
                        ->when(
                            $this->getSite(),
                            fn (EloquentBuilder $query, Site $site): EloquentBuilder => $query->where(
                                fn (EloquentBuilder $query): EloquentBuilder => $query->where('site_id', $site->getKey())
                                    ->orWhereNull('site_id'),
                            ),
                        )
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(
                            fn (Layout $layout): array => [$layout->id => $layout->name . ' (' . $layout->pages_count . ')'],
                        )
                        ->all(),
                )
                ->default(function () {
                    /** @var class-string<Layout> $model */
                    $model = Layout::class;

                    return $model::query()->default()->first(['id'])?->id;
                })
                ->reactive()
                ->helperText(
                    function (?int $state): ?HtmlString {
                        if ($state === null || $state === 0) {
                            return null;
                        }

                        $layout = Layout::query()
                            ->when(
                                $this->getSite(),
                                fn (EloquentBuilder $query, Site $site): EloquentBuilder => $query->where(
                                    fn (EloquentBuilder $query): EloquentBuilder => $query
                                        ->where('site_id', $site->getKey())
                                        ->orWhereNull('site_id'),
                                ),
                            )
                            ->find($state);

                        if (! $layout instanceof Layout) {
                            return null;
                        }

                        $total = $layout->pages()->count();

                        return new HtmlString(
                            trans_choice(
                                'capell-layout-builder::message.layout_count_on_pages',
                                $total,
                                [
                                    'count' => $total,
                                    'url' => AdminSurfaceLookup::resource(ResourceEnum::Page)::getUrl(
                                        'index',
                                        ['filters' => ['layout_id' => ['value' => $state]]],
                                    ),
                                ],
                            ),
                        );
                    },
                ),
        ];
    }

    protected function saveElementForm(Schema $configurator, Element $record, array $data): void
    {
        $this->assertCanUpdateLayout();

        $this->ensureLoaded();

        $configurator->saveRelationships();

        $record->update($data);
    }

    private function layoutBuilderActionFactory(): LayoutBuilderActionFactory
    {
        /** @var LayoutBuilder $livewire */
        $livewire = $this;

        return new LayoutBuilderActionFactory($livewire);
    }
}
