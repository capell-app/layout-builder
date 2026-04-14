<?php

declare(strict_types=1);

namespace Capell\Assistant\Actions;

use Capell\Assistant\Contracts\AiActionContextInterface;
use Capell\Assistant\Events\AiGenerationCompleted;
use Capell\Assistant\Events\AiGenerationFailed;
use Capell\Assistant\Events\AiGenerationStarted;
use Capell\Assistant\Support\Pipelines\GenerateContentPipeline;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

class GeneratorPageContentAction
{
    use AsAction;

    public function __construct(private readonly GenerateContentPipeline $pipeline) {}

    /**
     * @param  array{user_id?:int|null,current_title?:string|null,target_length?:int|null,refactor?:bool|null}  $options
     */
    public function handle(AiActionContextInterface $context, array $options = []): string
    {
        $startTime = microtime(true);
        Event::dispatch(new AiGenerationStarted(static::class, [$context, $options]));

        try {
            throw_unless($context instanceof AiActionContextInterface, InvalidArgumentException::class, 'Invalid context');
            throw_unless(is_array($options), InvalidArgumentException::class, 'Options must be an array');

            $input = [
                'context' => $context,
                'options' => $options,
                'action' => $this,
            ];

            $result = $this->pipeline->execute($input);

            $duration = microtime(true) - $startTime;

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
