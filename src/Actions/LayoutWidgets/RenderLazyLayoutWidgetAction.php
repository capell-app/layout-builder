<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\LayoutWidgets;

use Capell\Frontend\Actions\AssertPublicHtmlContainsNoAuthoringSurfaceAction;
use Capell\Frontend\Exceptions\WidgetLibraryException;
use Capell\Frontend\Support\Render\PublicViewQueryGuard;
use Capell\LayoutBuilder\Actions\WidgetExtensions\BuildPublicWidgetPayloadsAction;
use Capell\LayoutBuilder\Actions\WidgetSnapshots\ResolvePublicWidgetSnapshotAction;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\LayoutBuilder\Support\WidgetSnapshots\WidgetSnapshotResourceIds;
use Lorisleiva\Actions\Concerns\AsObject;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RenderLazyLayoutWidgetAction
{
    use AsObject;

    public function __construct(
        private readonly BuildPublicWidgetPayloadsAction $payloadBuilder,
        private readonly PublicViewQueryGuard $queryGuard,
        private readonly ResolvePublicWidgetSnapshotAction $snapshotResolver,
        private readonly WidgetExtensionRegistry $registry,
        private readonly WidgetSnapshotResourceIds $resourceIds,
    ) {}

    public function handle(string $reference): ?Response
    {
        try {
            $resolved = $this->snapshotResolver->handle($reference);
            if ($resolved === null) {
                return null;
            }
            $widgetData = $resolved['widget'];
            $renderContext = $resolved['context'];
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

            $definition = $this->registry->definition($resolved['snapshot']->widget_key);
            if ($definition === null) {
                return null;
            }
            $resourceIds = $this->resourceIds->resolve($definition->resourceGroups);
            if ($resourceIds === null) {
                return null;
            }

            $response = request()->expectsJson()
                || request()->header('Accept') === 'application/vnd.capell.widget.v2+json'
                ? response()->json([
                    'version' => 2,
                    'status' => 'ok',
                    'html' => $html,
                    'resource_ids' => $resourceIds,
                ], Response::HTTP_OK)
                : response($html, Response::HTTP_OK);
            $response->headers->add([
                'Cache-Control' => 'private, no-store',
                'X-Robots-Tag' => 'noindex, nofollow',
            ]);

            AssertPublicHtmlContainsNoAuthoringSurfaceAction::run(response($html));

            return $response;
        } catch (Throwable $throwable) {
            report($throwable);

            return null;
        }
    }
}
