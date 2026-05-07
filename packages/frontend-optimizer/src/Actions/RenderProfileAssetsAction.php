<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Actions;

use Capell\FrontendOptimizer\Support\RenderProfileAssetRenderer;
use Illuminate\Support\HtmlString;
use Lorisleiva\Actions\Concerns\AsAction;

class RenderProfileAssetsAction
{
    use AsAction;

    public function __construct(private readonly RenderProfileAssetRenderer $renderer) {}

    public function handle(string $profileHash): HtmlString
    {
        return $this->renderer->render($profileHash);
    }
}
