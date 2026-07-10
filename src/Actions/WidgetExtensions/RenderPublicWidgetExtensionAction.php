<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\WidgetExtensions;

use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Data\PublicPageRenderData;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionRenderContextData;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionViewResolver;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class RenderPublicWidgetExtensionAction
{
    public function __construct(
        private WidgetExtensionRegistry $registry,
        private WidgetExtensionViewResolver $viewResolver,
        private Factory $views,
    ) {}

    /**
     * @param  array<string, mixed>  $widgetData
     * @param  array<string, object>|PublicPageRenderData|null  $payloadSource
     */
    public function render(array $widgetData, PublicPageRenderData|array|null $payloadSource = null): string
    {
        $type = $widgetData['type'] ?? null;
        $definition = is_string($type) ? $this->registry->definition($type) : null;
        if ($definition === null) {
            return '';
        }

        $data = is_array($widgetData['data'] ?? null) ? $widgetData['data'] : [];
        $capell = is_array($data['__capell'] ?? null) ? $data['__capell'] : [];
        $instanceId = $capell['instance_id'] ?? null;
        $payload = is_string($instanceId) ? $this->payload($instanceId, $payloadSource) : null;
        $renderClass = $definition->renderData;

        if (! $payload instanceof $renderClass) {
            return $this->fallback();
        }

        try {
            return $this->views->make($this->viewResolver->resolve($definition), [
                'widget' => $payload,
                'context' => $this->safeContext(),
            ])->render();
        } catch (Throwable $throwable) {
            $this->diagnostic($definition->key, $throwable);

            return $this->fallback();
        }
    }

    /** @param array<string, object>|PublicPageRenderData|null $payloadSource */
    private function payload(string $instanceId, PublicPageRenderData|array|null $payloadSource): ?object
    {
        if (is_array($payloadSource)) {
            return $payloadSource[$instanceId] ?? null;
        }

        return ($payloadSource ?? $this->currentRenderData())?->contentWidgetPayload($instanceId);
    }

    private function currentRenderData(): ?PublicPageRenderData
    {
        if (! app()->bound(FrontendContextReader::class)) {
            return null;
        }

        $data = resolve(FrontendContextReader::class)->getFrontendData('publicPageRenderData');

        return $data instanceof PublicPageRenderData ? $data : null;
    }

    private function safeContext(): WidgetExtensionRenderContextData
    {
        $languageCode = app()->bound(FrontendContextReader::class)
            ? resolve(FrontendContextReader::class)->language()?->code
            : null;

        return new WidgetExtensionRenderContextData($languageCode);
    }

    private function fallback(): string
    {
        return $this->views->make('capell-layout-builder::components.layout-widgets.extension-unavailable')->render();
    }

    private function diagnostic(string $widgetKey, Throwable $throwable): void
    {
        try {
            Log::warning('Widget extension public rendering failed.', [
                'widget_key' => $widgetKey,
                'failure_type' => $throwable::class,
            ]);
        } catch (Throwable) {
            // Rendering remains isolated from diagnostics.
        }
    }
}
