<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Http\Controllers;

use Capell\Core\Models\Site;
use Capell\Frontend\Facades\Frontend;
use Capell\SeoSuite\Actions\BuildAiRobotsTxtRulesAction;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;

class RobotsTxtController extends BaseController
{
    public function __invoke(): Response
    {
        $site = Frontend::site();
        $content = BuildAiRobotsTxtRulesAction::run($site instanceof Site ? $site : null);

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'public, max-age=300',
            'ETag' => '"' . hash('sha256', $content) . '"',
        ]);
    }
}
