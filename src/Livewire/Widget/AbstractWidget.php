<?php

declare(strict_types=1);

namespace Capell\Mosaic\Livewire\Widget;

use Capell\Core\Enums\AssetComponentEnum;
use Capell\Frontend\Facades\Frontend;
use Capell\Mosaic\Enums\CapellLayoutCacheKeyEnum;
use Capell\Mosaic\Models\Widget;
use Closure;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Drawer\Utils;
use stdClass;

/**
 * @property-read Widget $widget
 */
abstract class AbstractWidget extends Component
{
    public array $container;

    public string $containerKey;

    public ?stdClass $loop = null;

    public int $occurrence;

    public array $widgetData = [];

    protected static string $defaultView = 'capell-mosaic::components.widget.default';

    protected bool $skipRender = false;

    abstract protected function mountWidget(): void;

    public static function getViewName(): string
    {
        return static::$defaultView;
    }

    /**
     * Get a Widget model by its key, with caching.
     */
    public static function getWidgetByKey(string $widgetKey): ?Widget
    {
        $cacheKey = CapellLayoutCacheKeyEnum::WidgetByKey->value . $widgetKey;

        return self::getCached(
            $cacheKey,
            fn () => Widget::query()->firstWhere('key', $widgetKey),
        );
    }

    public function hydrate(): void
    {
        $this->initializeWidget();
    }

    public function mount(
        string $containerKey,
        stdClass $loop,
        array $widgetData,
    ): void {
        $this->containerKey = $containerKey;
        $this->widgetData = $widgetData;
        $this->occurrence = $widgetData['occurrence'] ?? 1;
        $this->loop = $loop;

        $this->initializeWidget();
    }

    #[Computed]
    public function widget(): Widget
    {
        $widget = self::getWidgetByKey($this->widgetData['widget_key']);

        throw_if(! $widget instanceof Widget, Exception::class, 'Widget not found');

        return $widget;
    }

    public function render(array $data = []): View|Closure|string
    {
        if ($this->skipRender) {
            return Utils::insertAttributesIntoHtmlRoot('<div></div>', [
                'wire:id' => $this->getId(),
            ]);
        }

        $data = array_merge([
            'containerKey' => $this->containerKey,
            'component_item' => $this->getComponentItem(),
            'index' => $this->loop->index,
            'language' => Frontend::language(),
            'pageRecord' => Frontend::page(),
            'urlParams' => Frontend::params(),
            'site' => Frontend::site(),
            'theme' => Frontend::theme(),
            'widget' => $this->widget,
            'widgetData' => $this->widgetData,
        ], $data);

        return view($this->getComponent(), $data);
    }

    /**
     * Retrieve (and store if missing) a cached value using the array cache driver.
     */
    protected static function getCached(string $key, callable $resolver, bool $asBool = false): mixed
    {
        $cached = Cache::driver('array')->get($key);
        if ($cached !== null) {
            return $asBool ? (bool) $cached : $cached;
        }

        $result = $resolver();
        Cache::driver('array')->forever($key, $result);

        return $asBool ? (bool) $result : $result;
    }

    protected function getComponent(): string
    {
        return $this->widget->meta['view_file'] ?? $this->widget->type->meta['view_file'] ?? static::$defaultView;
    }

    protected function getComponentItem(): string
    {
        return $this->widget->meta['component_item'] ?? $this->widget->type->meta['component_item'] ?? $this->getDefaultComponentItem();
    }

    protected function getDefaultComponentItem(): string
    {
        return AssetComponentEnum::Card->value;
    }

    protected function initializeWidget(): void
    {
        $this->mountWidget();
    }
}
