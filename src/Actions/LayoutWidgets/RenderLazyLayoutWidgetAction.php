<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\LayoutWidgets;

use Capell\Frontend\Actions\AssertPublicHtmlContainsNoAuthoringSurfaceAction;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\Frontend\Exceptions\WidgetLibraryException;
use Capell\Frontend\Support\Render\PublicViewQueryGuard;
use Capell\Frontend\Support\Widgets\OpaqueWidgetReference;
use Capell\LayoutBuilder\Actions\WidgetExtensions\BuildPublicWidgetPayloadsAction;
use Capell\LayoutBuilder\Enums\LayoutWidgetTarget;
use Capell\LayoutBuilder\Support\LayoutWidgets\LayoutWidgetRegistry;
use Illuminate\Support\Arr;
use Lorisleiva\Actions\Concerns\AsObject;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RenderLazyLayoutWidgetAction
{
    use AsObject;

    public function __construct(
        private readonly BuildPublicWidgetPayloadsAction $payloadBuilder,
        private readonly PublicViewQueryGuard $queryGuard,
    ) {}

    public function handle(string $reference): ?Response
    {
        try {
            $data = OpaqueWidgetReference::decode($reference);

            if ($data === null) {
                return null;
            }

            $widgetData = $this->widgetData($data);
            $renderContext = new FrontendRenderContextData(null, null, null, null, null);
            $widgetPayloads = $this->payloadBuilder->buildForSources([$widgetData], $renderContext);

            $html = $this->queryGuard->guard($renderContext, fn (): string => view(
                'capell-layout-builder::components.layout-widgets.interaction-target',
                [
                    'widgetData' => $widgetData,
                    'widgetPayloads' => $widgetPayloads,
                    'context' => [],
                ],
            )->render());
            throw_unless(is_string($html), WidgetLibraryException::class, 'Lazy widget view did not render HTML.');

            $response = response($html, Response::HTTP_OK, [
                'Cache-Control' => 'public, max-age=300, stale-while-revalidate=60',
                'Content-Type' => 'text/html; charset=UTF-8',
                'X-Robots-Tag' => 'noindex, nofollow',
            ]);

            AssertPublicHtmlContainsNoAuthoringSurfaceAction::run($response);

            return $response;
        } catch (Throwable $throwable) {
            report($throwable);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{type: string, data: array<string, mixed>}
     */
    private function widgetData(array $data): array
    {
        $type = $data['type'] ?? null;
        $widgetPayload = $data['data'] ?? [];

        throw_unless(is_string($type) && $type !== '', WidgetLibraryException::class, 'Lazy widget reference is missing a widget type.');
        throw_unless(is_array($widgetPayload), WidgetLibraryException::class, 'Lazy widget reference data must be an array.');
        throw_unless(resolve(LayoutWidgetRegistry::class)->get($type, LayoutWidgetTarget::FrontendBlade) !== null, WidgetLibraryException::class, 'Lazy widget type is not registered.');

        Arr::forget($widgetPayload, '__capell.editor');

        return [
            'type' => $type,
            'data' => $widgetPayload,
        ];
    }
}
