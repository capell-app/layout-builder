<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Http\Controllers;

use Capell\LayoutBuilder\Actions\Fragments\RenderPublicFragmentAction;
use Illuminate\Http\Response;

final class PublicFragmentController
{
    public function __invoke(string $reference): Response
    {
        $html = RenderPublicFragmentAction::run($reference);

        abort_if($html === null, Response::HTTP_NOT_FOUND);

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=300, stale-while-revalidate=60')
            ->header('X-Robots-Tag', 'noindex');
    }
}
