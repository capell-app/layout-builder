<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Http\Controllers;

use Capell\Frontend\Facades\Frontend;
use Capell\SeoSuite\Actions\GenerateLlmsTxtAction;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Cache;

class LlmsTxtController extends BaseController
{
    public function __invoke(): Response
    {
        $site = Frontend::site();
        $language = Frontend::language();

        abort_if($site->getMeta('llms_txt_enabled') === false, 404);

        $cacheKey = sprintf('llms_txt_%d_%d', $site->id, $language->id);

        $content = Cache::remember($cacheKey, 3600, fn (): string => GenerateLlmsTxtAction::run($site, $language));

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
