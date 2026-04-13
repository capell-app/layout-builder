<?php

declare(strict_types=1);

namespace Capell\Layout\Livewire\Filament;

use Capell\Admin\Actions\NotifyClearCachedPagesAction;
use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Concerns\HasPageCacheNotification;
use Capell\Admin\Filament\Contracts\HasPageResource;
use Capell\Core\Actions\GetResourceFromTypeAction;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Layout\Livewire\Filament\Concerns\HasLayoutActions;
use Capell\Layout\Livewire\Filament\Concerns\ManagesAssets;
use Capell\Layout\Livewire\Filament\Concerns\ManagesContainers;
use Capell\Layout\Livewire\Filament\Concerns\ManagesWidgets;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection as SupportCollection;
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

    protected array $containerWidgets;

    protected string $view = 'capell-layout::livewire.filament.layout-builder.index';

    public static function getResource(): string
    {
        return CapellAdmin::getResource(ResourceEnum::Page);
    }

    public function mount(): void
    {
        $this->loadNew();
    }

    public function boot(): void
    {
        throw_if(! Filament::auth()->check(), AuthenticationException::class);
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
                ->body(__('capell-layout::message.layout_saved'))
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
        if ($widgets === []) {
            Notification::make('no-widgets-selected')
                ->body(__('capell-layout::message.no_widgets_selected'))
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

    #[On('sync-selected-assets')]
    public function addAssetsToWidget(array $arguments, string $type, array $assets): void
    {
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

        return CapellAdmin::getResource(ResourceEnum::Page);
    }

    /**
     * @return class-string<resource>
     */
    public function getCurrentResource(): string
    {
        if ($this->inPageContext()) {
            return $this->getPageResource();
        }

        return CapellAdmin::getResource(ResourceEnum::Layout);
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
