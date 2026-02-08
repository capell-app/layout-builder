<?php

declare(strict_types=1);

namespace Capell\Assistant\Actions;

use Capell\Assistant\Contracts\AiActionContextInterface;
use Capell\Assistant\Events\AiGenerationCompleted;
use Capell\Assistant\Events\AiGenerationFailed;
use Capell\Assistant\Events\AiGenerationStarted;
use Capell\Assistant\Models\AIGenerationHistory;
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
     * @param  array|Capell\Assistant\Contracts\AiActionContextInterface  $input
     */
    public function handle($input, array $options = []): AIGenerationHistory
    {
        $startTime = microtime(true);
        Event::dispatch(new AiGenerationStarted(static::class, [$input, $options]));

        try {
            if (is_array($input)) {
                /** @var array{action:string,model:string,input:string,output:string,prompt_tokens:int,completion_tokens:int,total_tokens:int,duration:float,metadata:array} $data */
                $data = $input;
                $history = AIGenerationHistory::query()->create($data);
                $duration = microtime(true) - $startTime;
                Log::info('AI Action completed', [
                    'action' => static::class,
                    'duration_ms' => round($duration * 1000, 2),
                ]);
                Event::dispatch(new AiGenerationCompleted(static::class, $history, []));

                return $history;
            }

            if (! $input instanceof AiActionContextInterface) {
                throw new InvalidArgumentException('Invalid input for RecordAiGenerationAction');
            }

            // Fallback: create with minimal/default data
            $history = AIGenerationHistory::query()->create([]);
            $duration = microtime(true) - $startTime;
            Log::info('AI Action completed', [
                'action' => static::class,
                'duration_ms' => round($duration * 1000, 2),
            ]);
            Event::dispatch(new AiGenerationCompleted(static::class, $history, []));

            return $history;
        } catch (Throwable $e) {
            Log::error('AI Action failed', [
                'action' => static::class,
                'error' => $e->getMessage(),
            ]);
            Event::dispatch(new AiGenerationFailed(static::class, $e));
            throw $e;
        }
    }
}
