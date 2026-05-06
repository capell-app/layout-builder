<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\SeoSuite\Data\Ai\AiGenerationInputData;
use Capell\SeoSuite\DataObjects\AiCreatorData;
use Capell\SeoSuite\Events\AiGenerationCompleted;
use Capell\SeoSuite\Events\AiGenerationFailed;
use Capell\SeoSuite\Events\AiGenerationStarted;
use Capell\SeoSuite\Support\Pipelines\AiCreatorPipeline;
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
            $result = $this->pipeline->execute(AiGenerationInputData::forAiCreator('ai_creator_layout', $data));
            $sections = (array) $result->output;

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
