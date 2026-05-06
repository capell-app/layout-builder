<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Listeners;

use Capell\SeoSuite\Events\AiGenerationFailed;
use Illuminate\Support\Facades\Log;

class NotifyAiFailure
{
    public function handle(AiGenerationFailed $event): void
    {
        Log::warning('AI generation failed', [
            'action' => $event->actionClass,
            'error' => $event->exception->getMessage(),
        ]);
    }
}
