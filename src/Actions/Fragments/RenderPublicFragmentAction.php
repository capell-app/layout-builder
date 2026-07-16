<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\Fragments;

use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Frontend\Actions\AssertPublicHtmlContainsNoAuthoringSurfaceAction;
use Capell\Frontend\Actions\Fragments\ResolvePublicFragmentContextAction;
use Capell\Frontend\Contracts\Fragments\PublicFragmentReferenceCodec;
use Capell\Frontend\Data\Fragments\PublicFragmentContextData;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\CapellFrontendContext;
use Capell\Frontend\Support\State\FrontendState;
use Capell\LayoutBuilder\Actions\BuildPublicLayoutGraphAction;
use Capell\LayoutBuilder\Data\PublicLayoutContainerData;
use Capell\LayoutBuilder\Data\PublicLayoutWidgetData;
use Capell\LayoutBuilder\Fragments\LayoutBuilderFragmentUrlResolver;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;

class RenderPublicFragmentAction
{
    use AsFake;
    use AsObject;

    public function handle(string $reference): ?string
    {
        try {
            return $this->render($reference);
        } catch (Throwable $throwable) {
            report($throwable);

            return null;
        }
    }

    private function render(string $reference): ?string
    {
        $decoded = resolve(PublicFragmentReferenceCodec::class)->decode($reference);

        if ($decoded->owner !== LayoutBuilderFragmentUrlResolver::OWNER) {
            return null;
        }

        $context = ResolvePublicFragmentContextAction::run($decoded);
        $page = $context->page;
        $layout = $page->getRelationValue('layout');
        $containerKey = $this->stringValue($decoded->ownerContext['containerKey'] ?? null);
        $widgetKey = $this->stringValue($decoded->ownerContext['widgetKey'] ?? null);
        $occurrence = $this->positiveInteger($decoded->ownerContext['occurrence'] ?? null);
        $widgetVersion = $this->stringValue($decoded->ownerContext['widgetVersion'] ?? null);

        if (! $page instanceof Page
            || ! $layout instanceof Layout
            || $containerKey === null
            || $widgetKey === null
            || $occurrence === null
            || $widgetVersion === null) {
            return null;
        }

        $widget = Widget::query()
            ->where('key', $widgetKey)
            ->whereHas('blueprint', fn (Builder $query): Builder => $query->enabled()->accessible())
            ->enabled()
            ->publishedDate()
            ->first();

        if (! $widget instanceof Widget
            || ! hash_equals(
                $widgetVersion,
                ResolveLayoutBuilderFragmentWidgetVersionAction::run(
                    $widget,
                    $page,
                    $context->language,
                    $containerKey,
                    $occurrence,
                ),
            )) {
            return null;
        }

        $previousFrontendContext = $this->resolvedFrontendContext();

        try {
            $this->bindFrontendContext($context, $layout, $page);

            $graph = BuildPublicLayoutGraphAction::run($layout, $page, $context->language, [$containerKey], includeHtml: true);
            $container = null;

            foreach ($graph->containers as $candidateContainer) {
                if ($candidateContainer->key === $containerKey) {
                    $container = $candidateContainer;

                    break;
                }
            }

            $widget = null;

            if ($container instanceof PublicLayoutContainerData) {
                foreach ($container->widgets as $candidateWidget) {
                    if ($candidateWidget->key === $widgetKey && $candidateWidget->occurrence === $occurrence) {
                        $widget = $candidateWidget;

                        break;
                    }
                }
            }

            if (! $widget instanceof PublicLayoutWidgetData || ! is_string($widget->html) || trim($widget->html) === '') {
                return null;
            }

            $response = new Response($widget->html);
            $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
            AssertPublicHtmlContainsNoAuthoringSurfaceAction::run($response);

            return $widget->html;
        } finally {
            $this->restoreFrontendContext($previousFrontendContext);
        }
    }

    private function bindFrontendContext(PublicFragmentContextData $context, Layout $layout, Page $page): void
    {
        $site = $context->site;
        $language = $context->language;
        $site->loadMissing('theme');
        $layout->loadMissing('theme');

        $theme = $this->frontendTheme($site, $layout);

        $page->setRelation('site', $site);
        $page->setRelation('layout', $layout);

        if (! $theme instanceof Theme) {
            return;
        }

        Frontend::clearResolvedInstance(CapellFrontendContext::class);
        app()->instance(
            CapellFrontendContext::class,
            new CapellFrontendContext(
                (new FrontendState)
                    ->withSite($site)
                    ->withLanguage($language)
                    ->withPage($page)
                    ->withLayout($layout)
                    ->withTheme($theme),
            ),
        );
    }

    private function frontendTheme(Site $site, Layout $layout): ?Theme
    {
        if ($site->theme instanceof Theme) {
            return $site->theme;
        }

        return $layout->theme instanceof Theme ? $layout->theme : null;
    }

    private function resolvedFrontendContext(): ?CapellFrontendContext
    {
        if (! app()->resolved(CapellFrontendContext::class)) {
            return null;
        }

        $context = resolve(CapellFrontendContext::class);

        return $context instanceof CapellFrontendContext ? $context : null;
    }

    private function restoreFrontendContext(?CapellFrontendContext $context): void
    {
        Frontend::clearResolvedInstance(CapellFrontendContext::class);

        if ($context instanceof CapellFrontendContext) {
            app()->instance(CapellFrontendContext::class, $context);

            return;
        }

        app()->forgetInstance(CapellFrontendContext::class);
    }

    private function stringValue(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private function positiveInteger(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        return max(1, (int) $value);
    }
}
