<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\SeoSuite\Actions\Ai\RecordAiGenerationAction as PersistAiGenerationAction;
use Capell\SeoSuite\Contracts\AiActionContextInterface;
use Capell\SeoSuite\Data\Ai\AiGenerationResultData;
use Capell\SeoSuite\Events\AiGenerationCompleted;
use Capell\SeoSuite\Events\AiGenerationFailed;
use Capell\SeoSuite\Events\AiGenerationStarted;
use Capell\SeoSuite\Models\AIGenerationHistory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

class RecordAiGenerationAction
{
    use AsAction;

    /**
     * Accepts a plain array payload and records a history entry. Falls back to context/options if not array.
     *
     * @param  array<string, mixed>|AiGenerationResultData|AiActionContextInterface  $input
     */
    public function handle($input, array $options = []): AIGenerationHistory
    {
        $startTime = microtime(true);
        Event::dispatch(new AiGenerationStarted(static::class, [$input, $options]));

        try {
            if (is_array($input) || $input instanceof AiGenerationResultData) {
                $history = resolve(PersistAiGenerationAction::class)->handle($input);
                $duration = microtime(true) - $startTime;
                Log::info('AI Action completed', [
                    'action' => static::class,
                    'duration_ms' => round($duration * 1000, 2),
                ]);
                Event::dispatch(new AiGenerationCompleted(static::class, $history, []));

                return $history;
            }

            throw_unless($input instanceof AiActionContextInterface, InvalidArgumentException::class, 'Invalid input for RecordAiGenerationAction');

            $history = resolve(PersistAiGenerationAction::class)->handle([
                'action' => static::class,
                'input' => $input->getContent(),
                'pageable_id' => $input->getPageId(),
                'pageable_type' => $input->getPageType(),
                'language_id' => $input->getLanguageId(),
                'metadata' => $options,
            ]);
            $duration = microtime(true) - $startTime;
            Log::info('AI Action completed', [
                'action' => static::class,
                'duration_ms' => round($duration * 1000, 2),
            ]);
            Event::dispatch(new AiGenerationCompleted(static::class, $history, []));

            return $history;
        } catch (Throwable $throwable) {
            Log::error('AI Action failed', [
                'action' => static::class,
                'error' => $throwable->getMessage(),
            ]);
            Event::dispatch(new AiGenerationFailed(static::class, $throwable));
            throw $throwable;
        }
    }
}
