<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Http\Controllers;

use Capell\Core\Support\PublicRead\PublicReadProjectionHeaders;
use Capell\LayoutBuilder\Actions\Fragments\RenderPublicFragmentAction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class PublicFragmentController
{
    public function __invoke(Request $request, string $reference): Response
    {
        $result = RenderPublicFragmentAction::make()->result($reference);

        // The outcome is never echoed: a crashed render answers with a bare 500
        // and every other non-rendered outcome with the same bare 404, so the
        // public response cannot be used to probe render internals. The reason
        // lives only in the server-side log written by the action.
        $html = $result->html;

        if (! $result->outcome->isRendered()) {
            return response('', $result->outcome->httpStatus());
        }

        if (! is_string($html) || $html === '') {
            return response('', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Revalidate on every request: a fragment whose page was gated after publication must not outlive a shared cache copy.
        return PublicReadProjectionHeaders::apply(
            response($html)
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('X-Robots-Tag', 'noindex'),
            $request,
            $html,
        );
    }
}
