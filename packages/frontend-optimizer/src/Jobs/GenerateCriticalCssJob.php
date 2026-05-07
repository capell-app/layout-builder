<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Jobs;

use Capell\FrontendOptimizer\Actions\GenerateCriticalCssAction;
use Capell\FrontendOptimizer\Models\FrontendRenderProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateCriticalCssJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $renderProfileId,
        public string $url,
    ) {}

    public function handle(GenerateCriticalCssAction $action): void
    {
        $profile = FrontendRenderProfile::query()->findOrFail($this->renderProfileId);

        $action->handle($profile, $this->url);
    }
}
