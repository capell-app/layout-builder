<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions;

use Capell\SeoTools\DataObjects\AiCreatorData;
use Capell\SeoTools\Events\AiGenerationCompleted;
use Capell\SeoTools\Events\AiGenerationFailed;
use Capell\SeoTools\Events\AiGenerationStarted;
use Capell\SeoTools\Support\Pipelines\AiCreatorPipeline;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

class GenerateAiLayoutAction
{
    use AsAction;

    public function __construct(private readonly AiCreatorPipeline $pipeline) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(AiCreatorData $data): array
    {
        $startTime = microtime(true);
        Event::dispatch(new AiGenerationStarted(static::class, [$data]));

        try {
            $sections = $this->pipeline->execute($data);

            Event::dispatch(new AiGenerationCompleted(
                static::class,
                $sections,
                ['duration' => microtime(true) - $startTime],
            ));

            return $sections;
        } catch (Throwable $throwable) {
            Event::dispatch(new AiGenerationFailed(static::class, $throwable));

            throw $throwable;
        }
    }
}
