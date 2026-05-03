<?php

declare(strict_types=1);

namespace Capell\Mosaic\Livewire\Filament;

use Capell\Admin\Actions\NotifyClearCachedPagesAction;
use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Filament\Concerns\HasPageCacheNotification;
use Capell\Admin\Filament\Contracts\HasPageResource;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Core\Actions\GetResourceFromTypeAction;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Mosaic\Livewire\Filament\Concerns\HasLayoutActions;
use Capell\Mosaic\Livewire\Filament\Concerns\ManagesAssets;
use Capell\Mosaic\Livewire\Filament\Concerns\ManagesContainers;
use Capell\Mosaic\Livewire\Filament\Concerns\ManagesWidgets;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * @property-read ?Pageable $page
 * @property-read $changeLayoutAction
 * @property-read $duplicateLayoutAction
 * @property-read $addWidgetAction
 * @property-read $editWidgetAssetAction
 */
class LayoutBuilder extends Component implements HasActions, HasForms, HasPageResource
{
    use HasLayoutActions;
    use HasPageCacheNotification;
    use InteractsWithActions;
    use InteractsWithForms;
    use ManagesAssets;
    use ManagesContainers;
    use ManagesWidgets;

    #[Locked]
    public ?Pageable $page = null;

    #[Locked]
    public ?Site $site = null;

    #[Locked]
    public Layout $layout;

    #[Locked]
    public ?array $originalAssets = null;

    public ?array $containers = null;

    public array $assets = [];

    public array $selectedRecords;

    public bool $layoutModified = false;

    public string $widgetPaletteSearch = '';

    public int $widgetPalettePage = 1;

    public int $widgetPalettePerPage = 12;

    protected array $containerWidgets;

    protected string $view = 'capell-mosaic::livewire.filament.layout-builder.index';

    public static function getResource(): string
    {
        return AdminSurfaceLookup::resource(ResourceEnum::Page);
    }

    public function mount(): void
    {
        $this->assertCanUpdateLayout();

        $this->loadNew();
    }

    public function boot(): void
    {
        throw_if(! Filament::auth()->check(), AuthenticationException::class);
    }

    public function updatedWidgetPaletteSearch(): void
    {
        $this->resetWidgetPalettePage();
    }

    #[Computed]
    public function widgetPalette(): LengthAwarePaginator
    {
        $search = trim($this->widgetPaletteSearch);

        return $this->getWidgetQuery()
            ->when(
                $search !== '',
                fn (EloquentBuilder $query): EloquentBuilder => $query->where(
                    fn (EloquentBuilder $query): EloquentBuilder => $query
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('key', 'like', '%' . $search . '%')
                        ->orWhereHas(
                            'type',
                            fn (EloquentBuilder $query): EloquentBuilder => $query
                                ->where('name', 'like', '%' . $search . '%')
                                ->orWhere('key', 'like', '%' . $search . '%'),
                        )
                        ->orWhereHas(
                            'translations',
                            fn (EloquentBuilder $query): EloquentBuilder => $query->where('title', 'like', '%' . $search . '%'),
                        ),
                ),
            )
            ->ordered()
            ->paginate(
                perPage: $this->widgetPalettePerPage,
                page: $this->widgetPalettePage,
            );
    }

    public function resetWidgetPalettePage(): void
    {
        $this->widgetPalettePage = 1;
    }

    public function previousWidgetPalettePage(): void
    {
        $this->widgetPalettePage = max(1, $this->widgetPalettePage - 1);

        unset($this->widgetPalette);
    }

    public function nextWidgetPalettePage(): void
    {
        if ($this->widgetPalettePage >= $this->widgetPalette->lastPage()) {
            return;
        }

        $this->widgetPalettePage++;

        unset($this->widgetPalette);
    }

    #[Computed]
    public function layoutPagesCount(): int
    {
        if ($this->layout->hasAttribute('pages_count')) {
            return $this->layout->pages_count;
        }

        $this->layout->loadCount('pages');

        return $this->layout->pages_count;
    }

    #[On('save-layout')]
    public function saveLayout(bool $withNotifications = false): void
    {
        $this->assertCanUpdateLayout();

        if (! $this->layoutModified) {
            return;
        }

        $this->loadFromStore();

        $this->layout->update([
            'containers' => $this->containers,
        ]);

        if ($this->page && $this->page->layout_id !== $this->layout->getKey()) {
            $this->page->update([
                'layout_id' => $this->layout->getKey(),
            ]);
        }

        $processedWidgetKeys = [];

        foreach ($this->containers as $containerKey => $container) {
            foreach ($container['widgets'] as $widgetIndex => $widget) {
                if ($this->inPageContext() && isset($widget['pageable_type'], $widget['pageable_id'])) {
                    $key = $widget['widget_key'] . '_' . $widget['pageable_type'] . '_' . $widget['pageable_id'] . '_' . $widget['container'] . '_' . $widget['occurrence'];
                } else {
                    $key = $widget['widget_key'] . '_' . $widget['occurrence'];
                }

                if (in_array($key, $processedWidgetKeys, true)) {
                    continue;
                }

                $processedWidgetKeys[] = $key;

                $this->updateAssets($containerKey, $widgetIndex, $widget['old_container'] ?? null);
            }
        }

        if ($this->inPageContext()) {
            $this->deleteRemovedWidgetAssets();
        }

        $this->dispatch('layout-builder-reset');

        $this->layoutUpdated(false);

        if ($withNotifications) {
            Notification::make('layout-saved')
                ->body(__('capell-mosaic::message.layout_saved'))
                ->success()
                ->send();

            NotifyClearCachedPagesAction::run(
                collect([$this->layout])
                    ->when(
                        $this->page,
                        fn (SupportCollection $collection, Pageable $page): SupportCollection => $collection->push($page),
                    ),
            );
        }
    }

    #[On('add-widgets-to-container')]
    public function addWidgetsToContainer(string $containerKey, array $widgets, ?string $actionModalId = null): void
    {
        $this->assertCanUpdateLayout();

        if ($widgets === []) {
            Notification::make('no-widgets-selected')
                ->body(__('capell-mosaic::message.no_widgets_selected'))
                ->warning()
                ->send();

            return;
        }

        $this->ensureLoaded();

        foreach ($widgets as $widgetId) {
            $widget = $this->getWidget($widgetId);

            $widgetIndex = $this->addWidgetToContainer($widget, $containerKey);

            $widget = $this->loadWidget($containerKey, $widgetIndex);

            $this->assets[$containerKey][$widgetIndex] = $this->mapWidgetAssets($widget, $containerKey);

            $this->updatePageAssets($containerKey, $widgetIndex);
        }

        session(['layout-builder.container' => $containerKey]);

        $this->setupSelectedAssets();

        $this->layoutUpdated();

        if ($actionModalId) {
            $this->dispatch('close-modal', id: $actionModalId);
        }
    }

    public function addPaletteWidgetToContainer(int $widgetId, string $containerKey, ?int $position = null): void
    {
        $this->assertCanUpdateLayout();

        $this->ensureLoaded();

        $widget = $this->getWidget($widgetId);

        $widgetIndex = $this->addWidgetToContainerAtPosition($widget, $containerKey, $position);

        $widget = $this->loadWidget($containerKey, $widgetIndex);

        $this->assets[$containerKey][$widgetIndex] = $this->mapWidgetAssets($widget, $containerKey);

        $this->updatePageAssets($containerKey, $widgetIndex);

        session(['layout-builder.container' => $containerKey]);

        $this->setupSelectedAssets();

        $this->layoutUpdated();
    }

    #[On('sync-selected-assets')]
    public function addAssetsToWidget(array $arguments, string $type, array $assets): void
    {
        $this->assertCanUpdateLayout();

        $this->ensureLoaded();

        $containerKey = $arguments['containerKey'];
        $widgetIndex = $arguments['widgetIndex'];
        $hasPageAssets = $arguments['hasPageAssets'] ?? false;

        $this->addAssets($containerKey, $widgetIndex, $hasPageAssets, $type, $assets);

        $this->layoutUpdated();
    }

    /**
     * @return class-string<resource>
     */
    public function getPageResource(): string
    {
        if ($this->page) {
            $resource = GetResourceFromTypeAction::run(ResourceEnum::Page, $this->page->type);

            if ($resource !== null) {
                return $resource;
            }
        }

        return AdminSurfaceLookup::resource(ResourceEnum::Page);
    }

    /**
     * @return class-string<resource>
     */
    public function getCurrentResource(): string
    {
        if ($this->inPageContext()) {
            return $this->getPageResource();
        }

        return AdminSurfaceLookup::resource(ResourceEnum::Layout);
    }

    public function placeholder(array $params = []): View
    {
        return view('capell-admin::components.placeholder', $params);
    }

    public function render(): View
    {
        $this->ensureLoaded();

        return view($this->view);
    }

    protected function ensureLoaded(): void
    {
        if (! isset($this->containerWidgets)) {
            $this->loadFromStore();
        }
    }

    protected function loadNew(): void
    {
        $this->setupContainers();

        $widgets = $this->preloadAllWidgets();

        foreach (array_keys($this->containers) as $containerKey) {
            $this->setupContainerWidgets($containerKey, $widgets);
        }

        $this->setupSelectedAssets();

        $this->saveOriginalAssets();
    }

    protected function loadFromStore(): void
    {
        $this->setupContainers();

        $widgets = $this->preloadAllWidgets(withAssets: false);

        $allWidgetAssets = $this->preloadAllWidgetAssets();

        $containerWidgetAssets = [];

        foreach ($this->assets as $containerKey => $containerWidgets) {
            foreach ($containerWidgets as $widgetIndex => $widgetAssets) {
                $containerWidgetAssets[$containerKey][$widgetIndex] = $this->setupWidgetAssets(
                    $containerKey,
                    $widgetIndex,
                    $widgetAssets,
                    $allWidgetAssets,
                );
            }
        }

        foreach (array_keys($this->containers) as $containerKey) {
            $this->setupContainerWidgets($containerKey, $widgets, $containerWidgetAssets);
        }
    }

    protected function reload(): void
    {
        $this->reset('containerWidgets', 'selectedRecords', 'assets', 'originalAssets', 'containers', 'layout');

        $this->loadNew();
    }

    protected function inPageContext(): bool
    {
        return $this->page instanceof Pageable;
    }

    protected function assertCanUpdateLayout(): void
    {
        $actor = Filament::auth()->user();

        throw_if($actor === null, AuthenticationException::class);

        if ($this->page instanceof Model) {
            Gate::forUser($actor)->authorize('update', $this->page);
        }

        Gate::forUser($actor)->authorize('update', $this->layout);
    }

    protected function layoutUpdated(bool $modified = true): void
    {
        $this->layoutModified = $modified;
    }

    protected function getSite(): ?Site
    {
        if ($this->site instanceof Site) {
            return $this->site;
        }

        if (! $this->inPageContext()) {
            return null;
        }

        return $this->page->site;
    }
}
