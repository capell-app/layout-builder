<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions;

use Capell\SeoTools\Contracts\AiActionContextInterface;
use Capell\SeoTools\Events\AiGenerationCompleted;
use Capell\SeoTools\Events\AiGenerationFailed;
use Capell\SeoTools\Events\AiGenerationStarted;
use Capell\SeoTools\Support\Pipelines\SuggestTitlesPipeline;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

class SuggestPageTitlesAction
{
    use AsAction;

    public function __construct(private readonly SuggestTitlesPipeline $pipeline) {}

    /**
     * @return array<int, string>
     */
    public function handle(AiActionContextInterface $context, array $options = []): array
    {
        $startTime = microtime(true);
        Event::dispatch(new AiGenerationStarted(static::class, [$context, $options]));

        try {
            throw_unless($context instanceof AiActionContextInterface, InvalidArgumentException::class, 'Invalid context');

            $input = [
                'context' => $context,
                'options' => $options,
                'action' => $this,
            ];

            $result = $this->pipeline->execute($input);

            $duration = microtime(true) - $startTime;
            Log::info('AI Action completed', [
                'action' => static::class,
                'duration_ms' => round($duration * 1000, 2),
            ]);
            Event::dispatch(new AiGenerationCompleted(static::class, $result, []));

            return $result;
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
