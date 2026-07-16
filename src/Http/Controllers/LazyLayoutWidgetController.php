<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Http\Controllers;

use Capell\LayoutBuilder\Actions\LayoutWidgets\RenderLazyLayoutWidgetAction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class LazyLayoutWidgetController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $reference = $request->route('reference');
        if (! is_string($reference) || $reference === '') {
            return response('', Response::HTTP_NOT_FOUND, [
                'Cache-Control' => 'private, no-store',
                'X-Robots-Tag' => 'noindex, nofollow',
            ]);
        }

        return RenderLazyLayoutWidgetAction::run($reference) ?? response('', Response::HTTP_NOT_FOUND, [
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
