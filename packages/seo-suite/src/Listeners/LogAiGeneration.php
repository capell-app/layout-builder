<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Listeners;

use Capell\SeoSuite\Events\AiGenerationCompleted;
use Illuminate\Support\Facades\Log;

class LogAiGeneration
{
    public function handle(AiGenerationCompleted $event): void
    {
        Log::info('AI generation completed', [
            'action' => $event->actionClass,
            'result_preview' => is_string($event->result) ? mb_substr($event->result, 0, 120) : 'non-string',
        ]);
    }
}
