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
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Data\Fragments\PublicFragmentContextData;
use Capell\Frontend\Data\Fragments\PublicFragmentReferenceData;
use Capell\Frontend\Exceptions\PublicFragmentReferenceInvalid;
use Capell\Frontend\Exceptions\PublicRenderContractViolationException;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\State\FrontendState;
use Capell\LayoutBuilder\Actions\BuildPublicLayoutGraphAction;
use Capell\LayoutBuilder\Data\PublicFragmentRenderResultData;
use Capell\LayoutBuilder\Data\PublicLayoutContainerData;
use Capell\LayoutBuilder\Data\PublicLayoutWidgetData;
use Capell\LayoutBuilder\Enums\PublicFragmentRenderOutcome;
use Capell\LayoutBuilder\Fragments\LayoutBuilderFragmentUrlResolver;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use Psr\Log\LogLevel;
use Throwable;

class RenderPublicFragmentAction
{
    use AsFake;
    use AsObject;

    /**
     * Render a public fragment, discarding the reason it did not render.
     *
     * Kept as the `handle()`/`::run()` entry point so existing callers that only
     * need the HTML are unaffected. Callers that must distinguish a crashed
     * render from a legitimately absent fragment — the public controller, chiefly
     * — should call `result()` instead.
     */
    public function handle(string $reference): ?string
    {
        return $this->result($reference)->html;
    }

    /**
     * Render a public fragment, reporting the outcome that produced the result.
     *
     * The outcome is a server-side diagnostic. Never place it, the decoded
     * reference, or any exception detail in a public response body or header.
     */
    public function result(string $reference): PublicFragmentRenderResultData
    {
        $decoded = null;

        try {
            $decoded = resolve(PublicFragmentReferenceCodec::class)->decode($reference);

            return $this->render($decoded);
        } catch (PublicFragmentReferenceInvalid) {
            // The raw token is never logged: it is an encrypted capability token.
            return $this->failure(PublicFragmentRenderOutcome::InvalidReference);
        } catch (PublicRenderContractViolationException) {
            // An authoring-surface leak must always fail soft, and must stay
            // indistinguishable from an absent fragment in the public response.
            return $this->failure(PublicFragmentRenderOutcome::AuthoringSurfaceRejected, $decoded);
        } catch (Throwable $throwable) {
            return $this->failure(PublicFragmentRenderOutcome::RenderFailed, $decoded, $throwable);
        }
    }

    private function render(PublicFragmentReferenceData $decoded): PublicFragmentRenderResultData
    {
        if ($decoded->owner !== LayoutBuilderFragmentUrlResolver::OWNER) {
            return $this->failure(PublicFragmentRenderOutcome::ForeignOwner, $decoded);
        }

        try {
            $context = ResolvePublicFragmentContextAction::run($decoded);
        } catch (ModelNotFoundException) {
            return $this->failure(PublicFragmentRenderOutcome::ContextUnavailable, $decoded);
        }

        $page = $context->page;

        if (! $page instanceof Page) {
            return $this->failure(PublicFragmentRenderOutcome::PageUnavailable, $decoded);
        }

        $layout = $page->getRelationValue('layout');

        if (! $layout instanceof Layout) {
            return $this->failure(PublicFragmentRenderOutcome::LayoutUnavailable, $decoded);
        }

        $containerKey = $this->stringValue($decoded->ownerContext['containerKey'] ?? null);
        $widgetKey = $this->stringValue($decoded->ownerContext['widgetKey'] ?? null);
        $occurrence = $this->positiveInteger($decoded->ownerContext['occurrence'] ?? null);
        $widgetVersion = $this->stringValue($decoded->ownerContext['widgetVersion'] ?? null);

        if ($containerKey === null) {
            return $this->failure(PublicFragmentRenderOutcome::MissingContainerKey, $decoded);
        }

        if ($widgetKey === null) {
            return $this->failure(PublicFragmentRenderOutcome::MissingWidgetKey, $decoded);
        }

        if ($occurrence === null) {
            return $this->failure(PublicFragmentRenderOutcome::MissingOccurrence, $decoded);
        }

        if ($widgetVersion === null) {
            return $this->failure(PublicFragmentRenderOutcome::MissingWidgetVersion, $decoded);
        }

        $widget = Widget::query()
            ->where('key', $widgetKey)
            ->whereHas('blueprint', fn (Builder $query): Builder => $query->enabled()->accessible())
            ->enabled()
            ->publishedDate()
            ->first();

        if (! $widget instanceof Widget) {
            return $this->failure(PublicFragmentRenderOutcome::WidgetUnavailable, $decoded);
        }

        if (! hash_equals(
            $widgetVersion,
            ResolveLayoutBuilderFragmentWidgetVersionAction::run(
                $widget,
                $page,
                $context->language,
                $containerKey,
                $occurrence,
            ),
        )) {
            return $this->failure(PublicFragmentRenderOutcome::WidgetVersionMismatch, $decoded);
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
                return $this->failure(PublicFragmentRenderOutcome::EmptyHtml, $decoded);
            }

            $response = new Response($widget->html);
            $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
            AssertPublicHtmlContainsNoAuthoringSurfaceAction::run($response);

            return PublicFragmentRenderResultData::rendered($widget->html);
        } finally {
            $this->restoreFrontendContext($previousFrontendContext);
        }
    }

    /**
     * Record why a fragment did not render, server-side only, and return the result.
     */
    private function failure(
        PublicFragmentRenderOutcome $outcome,
        ?PublicFragmentReferenceData $decoded = null,
        ?Throwable $throwable = null,
    ): PublicFragmentRenderResultData {
        $context = ['outcome' => $outcome->value];

        if ($decoded instanceof PublicFragmentReferenceData) {
            $context += [
                'owner' => $decoded->owner,
                'format_version' => $decoded->formatVersion,
                'pageable_id' => $decoded->pageableId,
                'site_id' => $decoded->siteId,
                'language_id' => $decoded->languageId,
            ];
        }

        if ($throwable instanceof Throwable) {
            $context['exception'] = $throwable;
        }

        $level = match ($outcome) {
            PublicFragmentRenderOutcome::RenderFailed => LogLevel::ERROR,
            PublicFragmentRenderOutcome::AuthoringSurfaceRejected => LogLevel::WARNING,
            default => LogLevel::DEBUG,
        };

        Log::log($level, 'Public layout fragment did not render.', $context);

        return PublicFragmentRenderResultData::failed($outcome);
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

        $state = (new FrontendState)
            ->withSite($site)
            ->withLanguage($language)
            ->withPage($page)
            ->withLayout($layout);

        if ($theme instanceof Theme) {
            $state->withTheme($theme);
        }

        Frontend::clearResolvedInstance(FrontendContextReader::class);
        app()->instance(FrontendContextReader::class, $state);
    }

    private function frontendTheme(Site $site, Layout $layout): ?Theme
    {
        if ($site->theme instanceof Theme) {
            return $site->theme;
        }

        return $layout->theme instanceof Theme ? $layout->theme : null;
    }

    private function resolvedFrontendContext(): ?FrontendContextReader
    {
        if (! app()->resolved(FrontendContextReader::class)) {
            return null;
        }

        $context = resolve(FrontendContextReader::class);

        return $context instanceof FrontendContextReader ? $context : null;
    }

    private function restoreFrontendContext(?FrontendContextReader $context): void
    {
        Frontend::clearResolvedInstance(FrontendContextReader::class);

        if ($context instanceof FrontendContextReader) {
            app()->instance(FrontendContextReader::class, $context);

            return;
        }

        app()->forgetInstance(FrontendContextReader::class);
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
