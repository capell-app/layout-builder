<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Illuminate\Http\Response;

/**
 * Why a public widget fragment render produced (or failed to produce) HTML.
 *
 * Every case is a server-side diagnostic only. Cases must never be surfaced in a
 * public response body or header: anonymous visitors only ever see the generic
 * status returned by `httpStatus()`.
 */
enum PublicFragmentRenderOutcome: string
{
    /** The fragment rendered and passed the public authoring-surface assertion. */
    case Rendered = 'rendered';

    /** The reference token could not be decoded (malformed, tampered, or wrong format version). */
    case InvalidReference = 'invalid_reference';

    /** The reference was decoded but belongs to another fragment owner. */
    case ForeignOwner = 'foreign_owner';

    /** The referenced page, site, language, layout, or content version is no longer publicly eligible. */
    case ContextUnavailable = 'context_unavailable';

    /** The resolved context did not carry a Page model. */
    case PageUnavailable = 'page_unavailable';

    /** The page no longer resolves to a Layout model. */
    case LayoutUnavailable = 'layout_unavailable';

    /** The reference owner context is missing a usable `containerKey`. */
    case MissingContainerKey = 'missing_container_key';

    /** The reference owner context is missing a usable `widgetKey`. */
    case MissingWidgetKey = 'missing_widget_key';

    /** The reference owner context is missing a usable `occurrence`. */
    case MissingOccurrence = 'missing_occurrence';

    /** The reference owner context is missing a usable `widgetVersion`. */
    case MissingWidgetVersion = 'missing_widget_version';

    /** No enabled, published, accessible widget matches the referenced widget key. */
    case WidgetUnavailable = 'widget_unavailable';

    /** The widget exists but its current version no longer matches the reference. */
    case WidgetVersionMismatch = 'widget_version_mismatch';

    /** The layout graph produced no widget occurrence, or its HTML was empty. */
    case EmptyHtml = 'empty_html';

    /** The rendered HTML failed the public authoring-surface assertion and was dropped. */
    case AuthoringSurfaceRejected = 'authoring_surface_rejected';

    /** The render threw an unexpected exception. */
    case RenderFailed = 'render_failed';

    public function isRendered(): bool
    {
        return $this === self::Rendered;
    }

    /**
     * Whether this outcome came from an unexpected server-side failure rather
     * than a legitimately absent, stale, or unsafe fragment.
     */
    public function isUnexpectedFailure(): bool
    {
        return $this === self::RenderFailed;
    }

    /**
     * The generic public status for this outcome. Crashes are honestly reported
     * as 500; everything else — including a rejected authoring surface — stays a
     * bare 404 so the public response cannot be used to probe render internals.
     */
    public function httpStatus(): int
    {
        return match ($this) {
            self::Rendered => Response::HTTP_OK,
            self::RenderFailed => Response::HTTP_INTERNAL_SERVER_ERROR,
            default => Response::HTTP_NOT_FOUND,
        };
    }
}
