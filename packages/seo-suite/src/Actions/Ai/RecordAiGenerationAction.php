<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions\Ai;

use Capell\SeoSuite\Data\Ai\AiGenerationResultData;
use Capell\SeoSuite\Models\AIGenerationHistory;
use Lorisleiva\Actions\Concerns\AsAction;

class RecordAiGenerationAction
{
    use AsAction;

    /**
     * @param  AiGenerationResultData|array<string, mixed>  $result
     */
    public function handle(AiGenerationResultData|array $result): AIGenerationHistory
    {
        if (is_array($result)) {
            return AIGenerationHistory::query()->create($result);
        }

        $metadata = array_merge($result->response?->metadata ?? [], $result->metadata);

        if ($result->messages !== null) {
            $metadata['ai_messages'] = $result->messages;
        }

        if ($result->params !== null) {
            $metadata['ai_params'] = $result->params;
        }

        if ($result->aiCreatorSessionId !== null) {
            $metadata['ai_creator_session_id'] = $result->aiCreatorSessionId;
        }

        return AIGenerationHistory::query()->create([
            'action' => $result->actionKey,
            'model' => $result->response?->model,
            'input' => $result->inputText,
            'output' => $result->outputText,
            'prompt_tokens' => (int) ($result->response?->metadata['prompt_tokens'] ?? 0),
            'completion_tokens' => (int) ($result->response?->metadata['completion_tokens'] ?? 0),
            'total_tokens' => $result->response?->tokensUsed ?? 0,
            'duration' => $result->response?->duration ?? 0,
            'pageable_id' => $result->pageableId,
            'pageable_type' => $result->pageableType,
            'language_id' => $result->languageId,
            'metadata' => $metadata,
        ]);
    }
}
